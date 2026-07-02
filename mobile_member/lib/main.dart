import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';

const _timsarOrigin = 'https://timsar.merliin.my.id';
const _cellChannel = MethodChannel('id.my.merliin.timsar_member/cell_info');

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const TimsarMemberApp());
}

class TimsarMemberApp extends StatelessWidget {
  const TimsarMemberApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'TIMSAR Anggota',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFDC2626),
          brightness: Brightness.light,
        ),
        scaffoldBackgroundColor: const Color(0xFFF8FAFC),
        useMaterial3: true,
      ),
      home: const TimsarWebShell(),
    );
  }
}

class TimsarWebShell extends StatefulWidget {
  const TimsarWebShell({super.key});

  @override
  State<TimsarWebShell> createState() => _TimsarWebShellState();
}

class _TimsarWebShellState extends State<TimsarWebShell> {
  late final WebViewController _webView;
  Timer? _cellTimer;
  double _progress = 0;
  String _cellStatus = 'Meminta izin lokasi...';

  @override
  void initState() {
    super.initState();
    _webView =
        WebViewController(onPermissionRequest: (request) => request.grant())
          ..setJavaScriptMode(JavaScriptMode.unrestricted)
          ..setBackgroundColor(const Color(0xFFF8FAFC))
          ..setNavigationDelegate(
            NavigationDelegate(
              onProgress: (progress) {
                if (mounted) setState(() => _progress = progress / 100);
              },
              onPageFinished: (_) {
                if (mounted) setState(() => _progress = 1);
                _publishCellInfo();
              },
            ),
          );

    _configureAndroidWebView();
    _prepareAndLoad();
  }

  Future<void> _configureAndroidWebView() async {
    final platform = _webView.platform;
    if (platform is! AndroidWebViewController) return;

    await platform.setGeolocationEnabled(true);
    await platform.setGeolocationPermissionsPromptCallbacks(
      onShowPrompt: (request) async {
        final allowed =
            Uri.tryParse(request.origin)?.host == Uri.parse(_timsarOrigin).host;
        return GeolocationPermissionsResponse(allow: allowed, retain: allowed);
      },
    );
  }

  Future<void> _prepareAndLoad() async {
    final status = await Permission.locationWhenInUse.request();
    if (!status.isGranted) {
      if (mounted) {
        setState(
          () => _cellStatus = 'Izin lokasi diperlukan untuk GPS dan BTS',
        );
      }
    } else if (mounted) {
      setState(() => _cellStatus = 'Mencari serving cell...');
    }

    await _webView.loadRequest(Uri.parse('$_timsarOrigin/login'));
    _cellTimer = Timer.periodic(
      const Duration(seconds: 5),
      (_) => _publishCellInfo(),
    );
  }

  Future<void> _publishCellInfo() async {
    try {
      final currentUrl = await _webView.currentUrl();
      if (Uri.tryParse(currentUrl ?? '')?.host !=
          Uri.parse(_timsarOrigin).host) {
        return;
      }

      final raw = await _cellChannel.invokeMapMethod<dynamic, dynamic>(
        'getServingCell',
      );
      if (raw == null || raw['cell_id'] == null) {
        if (mounted) setState(() => _cellStatus = 'BTS belum tersedia');
        return;
      }

      final cell = raw.map((key, value) => MapEntry(key.toString(), value));
      await _webView.runJavaScript(
        "window.dispatchEvent(new CustomEvent('timsar:cell-info', {detail: ${jsonEncode(cell)}}));",
      );

      if (mounted) {
        setState(() {
          final operator =
              cell['operator_label'] ?? cell['operator_name'] ?? 'Operator';
          _cellStatus =
              '$operator ${cell['radio_type']} / Cell ${cell['cell_id']}';
        });
      }
    } on PlatformException catch (error) {
      if (mounted) setState(() => _cellStatus = 'BTS: ${error.code}');
    } catch (_) {
      if (mounted) setState(() => _cellStatus = 'Menunggu halaman anggota...');
    }
  }

  Future<void> _handleBack() async {
    if (await _webView.canGoBack()) {
      await _webView.goBack();
    }
  }

  @override
  void dispose() {
    _cellTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _handleBack();
      },
      child: Scaffold(
        body: SafeArea(
          child: Column(
            children: [
              Container(
                height: 42,
                padding: const EdgeInsets.only(left: 14),
                decoration: const BoxDecoration(
                  color: Color(0xFF0F172A),
                  border: Border(bottom: BorderSide(color: Color(0xFF334155))),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.cell_tower,
                      size: 17,
                      color: Color(0xFFFBBF24),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        _cellStatus,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    IconButton(
                      tooltip: 'Muat ulang',
                      onPressed: _webView.reload,
                      icon: const Icon(
                        Icons.refresh,
                        size: 19,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
              ),
              if (_progress < 1)
                LinearProgressIndicator(
                  value: _progress,
                  minHeight: 2,
                  color: const Color(0xFFDC2626),
                  backgroundColor: const Color(0xFFF1F5F9),
                ),
              Expanded(child: WebViewWidget(controller: _webView)),
            ],
          ),
        ),
      ),
    );
  }
}
