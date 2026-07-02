package id.my.merliin.timsar_member

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.Build
import android.os.IBinder
import android.os.Looper
import android.telephony.CellIdentityNr
import android.telephony.CellInfoLte
import android.telephony.CellInfoNr
import android.telephony.CellSignalStrengthNr
import android.telephony.SubscriptionManager
import android.telephony.TelephonyManager
import androidx.core.app.NotificationCompat
import androidx.core.content.ContextCompat
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import java.util.concurrent.Executors
import java.util.concurrent.atomic.AtomicBoolean

class BackgroundTrackingService : Service(), LocationListener {
    companion object {
        const val ACTION_SYNC = "id.my.merliin.timsar_member.SYNC_BACKGROUND"
        const val ACTION_STOP = "id.my.merliin.timsar_member.STOP_BACKGROUND"
        const val EXTRA_COOKIE = "cookie"
        const val EXTRA_CSRF = "csrf"
        const val EXTRA_ACTIVE_URL = "active_url"
        const val EXTRA_LOCATION_URL = "location_url"
        const val EXTRA_HEARTBEAT_URL = "heartbeat_url"

        private const val PREFS = "timsar_background"
        private const val CHANNEL_SERVICE = "timsar_tracking"
        private const val CHANNEL_ASSIGNMENT = "timsar_assignment"
        private const val SERVICE_NOTIFICATION_ID = 4101
        private const val ASSIGNMENT_NOTIFICATION_ID = 4102
        private const val POLL_INTERVAL_MS = 10_000L
        private const val LOCATION_INTERVAL_MS = 5_000L
    }

    private val executor = Executors.newFixedThreadPool(2)
    private val pollInFlight = AtomicBoolean(false)
    private val handler = android.os.Handler(Looper.getMainLooper())
    private lateinit var locationManager: LocationManager
    private lateinit var notificationManager: NotificationManager
    private var locationUpdatesActive = false
    private var lastLocationSentAt = 0L

    private val pollRunnable = object : Runnable {
        override fun run() {
            pollAssignment()
            handler.postDelayed(this, POLL_INTERVAL_MS)
        }
    }

    override fun onCreate() {
        super.onCreate()
        locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        createNotificationChannels()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            stopLocationUpdates()
            stopForeground(STOP_FOREGROUND_REMOVE)
            stopSelf()
            return START_NOT_STICKY
        }

        if (intent?.action == ACTION_SYNC) {
            saveConfig(intent)
        }

        startForeground(SERVICE_NOTIFICATION_ID, serviceNotification("Siaga menerima tugas"))
        handler.removeCallbacks(pollRunnable)
        handler.post(pollRunnable)
        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        handler.removeCallbacks(pollRunnable)
        stopLocationUpdates()
        executor.shutdownNow()
        super.onDestroy()
    }

    override fun onLocationChanged(location: Location) {
        if (System.currentTimeMillis() - lastLocationSentAt < LOCATION_INTERVAL_MS) return
        lastLocationSentAt = System.currentTimeMillis()
        executor.execute { sendLocation(location) }
    }

    private fun pollAssignment() {
        val config = config() ?: return
        if (!pollInFlight.compareAndSet(false, true)) return
        executor.execute {
            try {
                sendHeartbeat(config)
                val connection = openConnection(config.activeUrl, "GET", config.cookie, config.csrf)
                val status = connection.responseCode
                if (status !in 200..299) return@execute

                val payload = connection.inputStream.bufferedReader().use { it.readText() }
                val assignment = JSONObject(payload).optJSONObject("assignment")
                if (assignment == null) {
                    handler.post {
                        stopLocationUpdates()
                        updateServiceNotification("Siaga menerima tugas")
                    }
                    return@execute
                }

                val assignmentId = assignment.optInt("id")
                val assignmentStatus = assignment.optString("status")
                val report = assignment.optJSONObject("report")
                val incident = report?.optString("incident_type").orEmpty().ifBlank { "Tugas darurat" }
                val trackingCode = report?.optString("tracking_code").orEmpty()

                notifyNewAssignment(assignmentId, incident, trackingCode)
                handler.post {
                    updateServiceNotification(if (assignmentStatus == "on_the_way") "Menuju lokasi: $incident" else "Tugas aktif: $incident")
                    if (assignmentStatus == "on_the_way") startLocationUpdates() else stopLocationUpdates()
                }
            } catch (_: Exception) {
                handler.post { updateServiceNotification("Koneksi terputus, mencoba kembali") }
            } finally {
                pollInFlight.set(false)
            }
        }
    }

    private fun startLocationUpdates() {
        if (locationUpdatesActive) return
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            updateServiceNotification("Izin lokasi diperlukan")
            return
        }

        try {
            var providerRegistered = false
            if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                locationManager.requestLocationUpdates(
                    LocationManager.GPS_PROVIDER,
                    LOCATION_INTERVAL_MS,
                    0f,
                    this,
                    Looper.getMainLooper(),
                )
                providerRegistered = true
            }
            if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                locationManager.requestLocationUpdates(
                    LocationManager.NETWORK_PROVIDER,
                    LOCATION_INTERVAL_MS,
                    0f,
                    this,
                    Looper.getMainLooper(),
                )
                providerRegistered = true
            }
            locationUpdatesActive = providerRegistered
            if (!providerRegistered) updateServiceNotification("Aktifkan GPS untuk pelacakan")
        } catch (_: Exception) {
            updateServiceNotification("GPS belum tersedia")
        }
    }

    private fun stopLocationUpdates() {
        if (!locationUpdatesActive) return
        try {
            locationManager.removeUpdates(this)
        } catch (_: Exception) {
            // Service tetap dapat melanjutkan polling tugas.
        }
        locationUpdatesActive = false
    }

    private fun sendLocation(location: Location) {
        val config = config() ?: return
        try {
            val body = JSONObject()
                .put("latitude", location.latitude)
                .put("longitude", location.longitude)
                .put("accuracy", location.accuracy.toDouble())
                .put("speed", if (location.hasSpeed()) location.speed * 3.6 else JSONObject.NULL)
                .put("network_type", networkType())
                .put("recorded_at", isoTimestamp(location.time))
                .put("cell", servingCellInfo() ?: JSONObject.NULL)
                .toString()

            val connection = openConnection(config.locationUrl, "POST", config.cookie, config.csrf)
            connection.setRequestProperty("Content-Type", "application/json")
            connection.doOutput = true
            connection.outputStream.use { it.write(body.toByteArray(Charsets.UTF_8)) }
            connection.responseCode
        } catch (_: Exception) {
            // Titik berikutnya akan mencoba kembali; service tidak dihentikan.
        }
    }

    private fun sendHeartbeat(config: Config) {
        try {
            val connection = openConnection(config.heartbeatUrl, "POST", config.cookie, config.csrf)
            connection.setRequestProperty("Content-Type", "application/json")
            connection.doOutput = true
            val body = JSONObject().put("network_type", networkType()).toString()
            connection.outputStream.use { it.write(body.toByteArray(Charsets.UTF_8)) }
            connection.responseCode
        } catch (_: Exception) {
            // Polling tugas tetap dilanjutkan pada siklus berikutnya.
        }
    }

    private fun openConnection(url: String, method: String, cookie: String, csrf: String): HttpURLConnection {
        return (URL(url).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 8_000
            readTimeout = 8_000
            instanceFollowRedirects = false
            setRequestProperty("Accept", "application/json")
            setRequestProperty("Cookie", cookie)
            setRequestProperty("X-CSRF-TOKEN", csrf)
            setRequestProperty("X-Requested-With", "XMLHttpRequest")
        }
    }

    private fun saveConfig(intent: Intent) {
        getSharedPreferences(PREFS, MODE_PRIVATE).edit()
            .putString(EXTRA_COOKIE, intent.getStringExtra(EXTRA_COOKIE).orEmpty())
            .putString(EXTRA_CSRF, intent.getStringExtra(EXTRA_CSRF).orEmpty())
            .putString(EXTRA_ACTIVE_URL, intent.getStringExtra(EXTRA_ACTIVE_URL).orEmpty())
            .putString(EXTRA_LOCATION_URL, intent.getStringExtra(EXTRA_LOCATION_URL).orEmpty())
            .putString(EXTRA_HEARTBEAT_URL, intent.getStringExtra(EXTRA_HEARTBEAT_URL).orEmpty())
            .apply()
    }

    private fun config(): Config? {
        val prefs = getSharedPreferences(PREFS, MODE_PRIVATE)
        val config = Config(
            cookie = prefs.getString(EXTRA_COOKIE, "").orEmpty(),
            csrf = prefs.getString(EXTRA_CSRF, "").orEmpty(),
            activeUrl = prefs.getString(EXTRA_ACTIVE_URL, "").orEmpty(),
            locationUrl = prefs.getString(EXTRA_LOCATION_URL, "").orEmpty(),
            heartbeatUrl = prefs.getString(EXTRA_HEARTBEAT_URL, "").orEmpty(),
        )
        return config.takeIf {
            it.cookie.isNotBlank() && it.activeUrl.isNotBlank() &&
                it.locationUrl.isNotBlank() && it.heartbeatUrl.isNotBlank()
        }
    }

    private fun notifyNewAssignment(assignmentId: Int, incident: String, trackingCode: String) {
        val prefs = getSharedPreferences(PREFS, MODE_PRIVATE)
        if (prefs.getInt("last_assignment_id", 0) == assignmentId) return
        prefs.edit().putInt("last_assignment_id", assignmentId).apply()

        notificationManager.notify(
            ASSIGNMENT_NOTIFICATION_ID,
            NotificationCompat.Builder(this, CHANNEL_ASSIGNMENT)
                .setSmallIcon(android.R.drawable.ic_dialog_alert)
                .setContentTitle("Tugas baru TIMSAR")
                .setContentText(listOf(trackingCode, incident).filter { it.isNotBlank() }.joinToString(" - "))
                .setPriority(NotificationCompat.PRIORITY_MAX)
                .setCategory(NotificationCompat.CATEGORY_ALARM)
                .setAutoCancel(true)
                .setContentIntent(openAppIntent())
                .build(),
        )
    }

    private fun serviceNotification(text: String) = NotificationCompat.Builder(this, CHANNEL_SERVICE)
        .setSmallIcon(android.R.drawable.ic_menu_mylocation)
        .setContentTitle("TIMSAR Anggota aktif")
        .setContentText(text)
        .setPriority(NotificationCompat.PRIORITY_LOW)
        .setOngoing(true)
        .setOnlyAlertOnce(true)
        .setContentIntent(openAppIntent())
        .build()

    private fun updateServiceNotification(text: String) {
        notificationManager.notify(SERVICE_NOTIFICATION_ID, serviceNotification(text))
    }

    private fun openAppIntent(): PendingIntent {
        val intent = packageManager.getLaunchIntentForPackage(packageName)?.apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        return PendingIntent.getActivity(
            this,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        notificationManager.createNotificationChannel(
            NotificationChannel(CHANNEL_SERVICE, "Status TIMSAR", NotificationManager.IMPORTANCE_LOW),
        )
        notificationManager.createNotificationChannel(
            NotificationChannel(CHANNEL_ASSIGNMENT, "Tugas darurat", NotificationManager.IMPORTANCE_HIGH).apply {
                description = "Pemberitahuan penugasan baru dari posko"
                enableVibration(true)
            },
        )
    }

    private fun networkType(): String {
        val manager = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val capabilities = manager.getNetworkCapabilities(manager.activeNetwork) ?: return "offline"
        return when {
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) -> "wifi"
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR) -> "cellular"
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET) -> "ethernet"
            else -> "unknown"
        }
    }

    private fun servingCellInfo(): JSONObject? {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            return null
        }

        val defaultManager = getSystemService(Context.TELEPHONY_SERVICE) as TelephonyManager
        val manager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
            val subscriptionId = SubscriptionManager.getActiveDataSubscriptionId()
            if (subscriptionId != SubscriptionManager.INVALID_SUBSCRIPTION_ID) {
                defaultManager.createForSubscriptionId(subscriptionId)
            } else {
                defaultManager
            }
        } else {
            defaultManager
        }
        val cell = try {
            manager.allCellInfo?.firstOrNull { it.isRegistered }
        } catch (_: Exception) {
            null
        } ?: return null

        val result = JSONObject()
            .put("operator_name", manager.networkOperatorName.takeIf { it.isNotBlank() })
            .put("network_operator_name", manager.networkOperatorName.takeIf { it.isNotBlank() })
            .put("network_operator_code", manager.networkOperator.takeIf { it.isNotBlank() })
            .put("operator_label", manager.networkOperatorName.takeIf { it.isNotBlank() })
            .put("is_registered", cell.isRegistered)

        when (cell) {
            is CellInfoLte -> {
                val identity = cell.cellIdentity
                val signal = cell.cellSignalStrength
                result
                    .put("radio_type", "LTE")
                    .put("mcc", if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) identity.mccString else cleanInt(identity.mcc))
                    .put("mnc", if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) identity.mncString else cleanInt(identity.mnc))
                    .put("cell_id", cleanInt(identity.ci)?.toString())
                    .put("tac_or_lac", cleanInt(identity.tac)?.toString())
                    .put("pci_or_psc", cleanInt(identity.pci)?.toString())
                    .put("signal_dbm", cleanSignal(signal.dbm))
                    .put("rsrp_dbm", if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) cleanSignal(signal.rsrp) else null)
                    .put("rsrq_db", if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) cleanSignal(signal.rsrq) else null)
            }
            is CellInfoNr -> {
                if (Build.VERSION.SDK_INT < Build.VERSION_CODES.Q) return null
                val identity = cell.cellIdentity as? CellIdentityNr ?: return null
                val signal = cell.cellSignalStrength as? CellSignalStrengthNr
                result
                    .put("radio_type", "NR")
                    .put("mcc", identity.mccString)
                    .put("mnc", identity.mncString)
                    .put("cell_id", cleanLong(identity.nci)?.toString())
                    .put("tac_or_lac", cleanInt(identity.tac)?.toString())
                    .put("pci_or_psc", cleanInt(identity.pci)?.toString())
                    .put("signal_dbm", signal?.let { cleanSignal(it.dbm) })
                    .put("rsrp_dbm", signal?.let { cleanSignal(it.ssRsrp) })
                    .put("rsrq_db", signal?.let { cleanSignal(it.ssRsrq) })
                    .put("sinr_db", signal?.let { cleanSignal(it.ssSinr) })
            }
            else -> return null
        }

        return result
    }

    private fun cleanInt(value: Int): Int? = value.takeIf { it >= 0 && it != Int.MAX_VALUE }

    private fun cleanLong(value: Long): Long? = value.takeIf { it >= 0 && it != Long.MAX_VALUE }

    private fun cleanSignal(value: Int): Int? = value.takeIf { it in -200..0 }

    private fun isoTimestamp(milliseconds: Long): String {
        return SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }.format(Date(milliseconds))
    }

    private data class Config(
        val cookie: String,
        val csrf: String,
        val activeUrl: String,
        val locationUrl: String,
        val heartbeatUrl: String,
    )
}
