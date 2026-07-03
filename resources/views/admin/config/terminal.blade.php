@extends('layouts.admin')

@section('page_title', 'System Master Terminal')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden bg-dark">
                <div
                    class="card-header bg-black text-white p-4 d-flex justify-content-between align-items-center border-bottom border-secondary">
                    <div>
                        <h4 class="mb-0 fw-bold"><i class="fas fa-terminal me-2 text-success"></i> Master System Console
                        </h4>
                        <p class="text-white-50 small mb-0 mt-1">Direct server-level execution | Artisan + Composer | Host:
                            {{ request()->getHost() }}</p>
                    </div>
                    <div class="status-indicator d-none d-md-block">
                        <span
                            class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-radiation-alt me-1 animate__animated animate__flash animate__infinite"></i>
                            HIGH-PRIVILEGE ACCESS
                        </span>
                    </div>
                </div>
                <div class="card-body p-0 bg-black">
                    <!-- TERMINAL DISPLAY -->
                    <div id="terminalDisplay" class="p-4 overflow-auto scrollbar-dark"
                        style="height: 550px; font-family: 'Courier New', Courier, monospace; line-height: 1.5;">
                        <div class="text-success mb-2">pmcc-laravel@admin:~$ welcome</div>
                        <div class="text-white-50 small mb-4">
                            * Authorized SuperAdmin multi-binary terminal access granted.<br>
                            * Supports: <span class="text-info">php artisan [cmd]</span> and <span
                                class="text-warning">composer [cmd]</span>.<br>
                            * Warning: Operations performed here are permanent.
                        </div>
                    </div>

                    <!-- COMMAND INPUT -->
                    <div class="p-3 bg-dark border-top border-secondary">
                        <form id="terminalForm" class="d-flex align-items-center">
                            <div class="text-success fw-bold me-2 ps-3">root@pmcc:~$</div>
                            <input type="text" id="commandInput"
                                class="form-control bg-transparent text-white border-0 shadow-none fw-bold"
                                placeholder="type command here... (e.g. composer install, artisan list)" autocomplete="off"
                                autofocus>
                            <button type="submit" id="runBtn" class="btn btn-success px-4 rounded-pill fw-bold me-3">
                                <i class="fas fa-play me-1"></i> RUN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="clearTerminal()">Clear
                    Console</button>
                <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="setCommand('optimize:clear')">Clear
                    Cache</button>
                <button class="btn btn-sm btn-outline-info rounded-pill px-3"
                    onclick="setCommand('route:list')">route:list</button>
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3"
                    onclick="setCommand('composer install')">composer install</button>
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3"
                    onclick="setCommand('composer dump-autoload')">composer dump-autoload</button>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        #terminalDisplay {
            scroll-behavior: smooth;
            background: #000;
        }

        #terminalDisplay::-webkit-scrollbar {
            width: 8px;
        }

        #terminalDisplay::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        .command-echo {
            color: #aaa;
            border-bottom: 1px solid #222;
            padding-bottom: 5px;
            margin-top: 15px;
        }

        .command-output {
            color: #fff;
            margin-bottom: 15px;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }

        .command-error {
            color: #ff4d4d;
        }

        #commandInput {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.1rem;
        }
    </style>
@endsection

@section('scripts')
    <script>
        const form = document.getElementById('terminalForm');
        const input = document.getElementById('commandInput');
        const display = document.getElementById('terminalDisplay');
        const btn = document.getElementById('runBtn');

        form.onsubmit = async (e) => {
            e.preventDefault();
            let cmd = input.value.trim();
            if (!cmd) return;

            // Clean cmd if they start with php artisan
            let echoCmd = cmd;

            // Echo command
            const echo = document.createElement('div');
            echo.className = 'command-echo';
            echo.innerHTML = `<span class="text-success">root@pmcc:~$</span> ${echoCmd}`;
            display.appendChild(echo);

            input.value = '';
            input.disabled = true;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await fetch(`{{ route('admin.config.terminal.run') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ command: cmd })
                });
                const data = await response.json();

                const out = document.createElement('div');
                out.className = 'command-output';
                out.innerText = data.output;
                display.appendChild(out);

                display.scrollTop = display.scrollHeight;
            } catch (e) {
                console.error(e);
                const err = document.createElement('div');
                err.className = 'command-output text-danger';
                err.innerText = 'FATAL: Connection Error / Timeout';
                display.appendChild(err);
            } finally {
                input.disabled = false;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-1"></i> RUN';
                input.focus();
            }
        };

        function clearTerminal() {
            display.innerHTML = '<div class="text-success mb-2">root@pmcc:~$ console cleared</div>';
        }

        function setCommand(cmd) {
            input.value = cmd;
            input.focus();
        }
    </script>
@endsection