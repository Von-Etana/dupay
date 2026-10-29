@extends('admin.layouts.app')
@section('style')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">
        
        <style>

    /* MAIN TERMINAL PANEL */
    .terminal {
      max-width: 1400px;
      width: 100%;
      background: #0c0f1c;
      background: linear-gradient(145deg, #0a0e17 0%, #070b12 100%);
      border-radius: 1.25rem;
      box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(0, 255, 255, 0.15), inset 0 1px 0 rgba(255,255,255,0.03);
      overflow: hidden;
    }

    /* TERMINAL HEADER (window controls + title) */
    .terminal-header {
      background: #141a24;
      padding: 0.9rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #2a3342;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
    }

    .window-controls {
      display: flex;
      gap: 10px;
    }

    .win-btn {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      transition: all 0.2s;
    }

    .close { background: #ff5f56; box-shadow: 0 0 2px #ff5f56; }
    .minimize { background: #ffbd2e; box-shadow: 0 0 2px #ffbd2e; }
    .maximize { background: #27c93f; box-shadow: 0 0 2px #27c93f; }

    .terminal-title {
      color: #9aaec7;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      background: #1e2633;
      padding: 0.2rem 1rem;
      border-radius: 30px;
      font-weight: 500;
      font-family: inherit;
      backdrop-filter: blur(2px);
    }

    .terminal-title i {
      font-style: normal;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .terminal-title i::before {
      content: "⚡";
      font-size: 0.8rem;
      filter: drop-shadow(0 0 2px #0ff);
    }

    /* SCROLLABLE LOG AREA */
    .log-container {
      height: 550px;
      overflow-y: auto;
      padding: 1.5rem 1.8rem;
      scroll-behavior: smooth;
      background: rgba(0, 0, 0, 0.3);
    }

    /* Custom scrollbar — terminal style */
    .log-container::-webkit-scrollbar {
      width: 8px;
      background: #0a0e17;
    }
    .log-container::-webkit-scrollbar-track {
      background: #0f141f;
      border-left: 1px solid #1f2a38;
    }
    .log-container::-webkit-scrollbar-thumb {
      background: #2d3e5a;
      border-radius: 10px;
    }
    .log-container::-webkit-scrollbar-thumb:hover {
      background: #3f5a7c;
    }

    /* LOG ENTRIES */
    .log-entry {
      font-family: 'Fira Code', 'JetBrains Mono', monospace;
      font-size: 0.85rem;
      line-height: 1.55;
      margin-bottom: 1rem;
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      border-left: 2px solid transparent;
      transition: 0.1s linear;
      padding: 0.2rem 0 0.2rem 0.8rem;
      word-break: break-word;
    }

    /* hover effect for logs (no JS, pure CSS) */
    .log-entry:hover {
      background: rgba(30, 60, 90, 0.2);
      border-left-color: #2d7f9e;
    }

    /* timestamp style */
    .log-timestamp {
      color: #5f7f9e;
      flex-shrink: 0;
      font-weight: 400;
      letter-spacing: -0.2px;
      user-select: none;
    }

    /* log level badges */
    .log-level {
      flex-shrink: 0;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.7rem;
      padding: 0.15rem 0.55rem;
      border-radius: 20px;
      background: #00000040;
      text-align: center;
      letter-spacing: 0.3px;
      backdrop-filter: blur(2px);
    }

    .level-error {
      color: #ff7a7a;
      background: rgba(255, 80, 80, 0.12);
      border: 0.5px solid rgba(255, 100, 100, 0.3);
    }
    .level-warning {
      color: #ffcd7e;
      background: rgba(255, 180, 60, 0.1);
      border: 0.5px solid #ffb34730;
    }
    .level-info {
      color: #6bc6ff;
      background: rgba(70, 160, 240, 0.1);
      border: 0.5px solid #3f8eff30;
    }
    .level-debug {
      color: #b7a0ff;
      background: rgba(150, 110, 255, 0.08);
      border: 0.5px solid #886eff30;
    }
    .level-critical {
      color: #ffb3b3;
      background: rgba(220, 40, 40, 0.2);
      border: 0.5px solid #ff5555;
      box-shadow: 0 0 2px #ff000020;
    }

    /* message content */
    .log-message {
      color: #cdd9f4;
      flex: 1;
      word-break: break-word;
    }

    .log-message strong {
      color: #ffb347;
      font-weight: 600;
    }

    .log-message .highlight {
      color: #ff8c6e;
      font-weight: bold;
    }

    /* error details / stack trace (optional) */
    .error-detail {
      margin-top: 0.45rem;
      margin-left: 1.5rem;
      font-size: 0.75rem;
      color: #7f8fa4;
      border-left: 1px dashed #2a3a4e;
      padding-left: 0.9rem;
      font-family: monospace;
      white-space: pre-wrap;
    }

    /* command line footer - static version (no input) */
    .terminal-footer {
      background: #0a0e17;
      border-top: 1px solid #1e2a36;
      padding: 0.8rem 1.5rem;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.7rem;
      font-size: 0.8rem;
      font-family: monospace;
    }

    .prompt {
      color: #2cd46e;
      font-weight: 500;
      display: inline-flex;
      gap: 5px;
    }

    .prompt-sign {
      color: #62ffa0;
    }

    .static-cmd {
      background: transparent;
      color: #c0e0ff;
      font-family: inherit;
      font-size: 0.8rem;
      flex: 1;
      letter-spacing: 0.3px;
    }

    .badge-live {
      background: #1f3526;
      color: #6eff9e;
      padding: 0.2rem 0.6rem;
      border-radius: 30px;
      font-size: 0.65rem;
      font-weight: bold;
      letter-spacing: 0.5px;
    }

    .badge-static {
      background: #2a2a3a;
      color: #aac8ff;
    }

    /* separator / ascii art line */
    .separator-line {
      margin: 0.5rem 0 1rem 0;
      color: #2e3b4e;
      font-size: 0.7rem;
      border-top: 1px dashed #253141;
      padding-top: 0.5rem;
    }

    /* responsive */
    @media (max-width: 680px) {
      body { padding: 0.8rem; }
      .log-container { padding: 1rem; }
      .log-entry { gap: 0.4rem; flex-wrap: wrap; }
      .log-timestamp { width: auto; }
      .terminal-title { font-size: 0.7rem; }
    }

    /* ASCII header for terminal vibe */
    .ascii-banner {
      font-family: monospace;
      font-size: 0.65rem;
      color: #2c6b5e;
      margin-bottom: 1.2rem;
      white-space: pre;
      line-height: 1.3;
      border-left: 2px solid #2c6b5e40;
      padding-left: 1rem;
    }

    /* cursor simulation in footer (blinking block) */
    .cursor-block {
      display: inline-block;
      width: 10px;
      height: 14px;
      background-color: #5effb2;
      vertical-align: middle;
      margin-left: 4px;
      animation: blink 1.2s step-end infinite;
    }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }
  </style>
@endsection
@section('title', $page_title)

@section('content')
<div class="layout-px-spacing">
	<div class="middle-content container-xxl p-0">
		<!-- BREADCRUMB -->
		<div class="page-meta">
			<nav class="breadcrumb-style-one" aria-label="breadcrumb">
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="/">Home</a></li>
					<li class="breadcrumb-item active" aria-current="page">Error Logs</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
			<div class="terminal">
            	<div class="terminal-header">
            		<div class="window-controls">
            			<div class="win-btn close"></div>
            			<div class="win-btn minimize"></div>
            			<div class="win-btn maximize"></div>
            		</div>
            		<div class="terminal-title">
            			<i>➜ ERROR_DAEMON :: ~/var/log/errors.terminal [static]</i>
            		</div>
            		<div></div>
            	</div>
            	<div class="log-container">
            	    @forelse($errors as $error)
            		<div class="log-entry">
            			<span class="log-timestamp">[{{ $error->created_at }}]</span>
            			<span class="log-level level-critical">{{ $error->model }}</span>
            			<div class="log-message">{{ $error->message }}</div>
            			<div class="error-detail">User : {{ $error->user_name }}</div>
            		</div>
            		@empty
            		<p>No errors found</p>
            		@endforelse
            	</div>
            	<div class="terminal-footer">
            		<div class="prompt">
            			<span class="prompt-sign">➜</span>
            			<span style="color:#8bb5e0;">error-logger</span>
            			<span style="color:#6f8fae;">$</span>
            		</div>
            		<div class="static-cmd">
            			cat /var/log/errors.log | grep -E "CRITICAL|ERROR" --color=always
            			<span class="cursor-block"></span>
            		</div>
            		<div class="badge-live badge-static">📟 STATIC</div>
            	</div>
            </div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/general.js') }}"></script>
    

@endsection