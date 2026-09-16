import { useEffect, useRef } from "react";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";
import "xterm/css/xterm.css";

const AGENT_ID = "100f0685-688b-46e9-b588-fcd3055865c7";

export default function Terminal_page() {
    const terminalRef = useRef<HTMLDivElement>(null);
    const wsRef = useRef<WebSocket | null>(null);
    const sessionIdRef = useRef<string | null>(null);

    useEffect(() => {
        if (!terminalRef.current) return;

        const terminal = new Terminal({
            cursorBlink: true,
            fontSize: 14,
            fontFamily: "monospace",
            convertEol: true,
            scrollback: 5000,
        });

        const fitAddon = new FitAddon();

        terminal.loadAddon(fitAddon);
        terminal.open(terminalRef.current);

        fitAddon.fit();

        const protocol =
            window.location.protocol === "https:" ? "wss" : "ws";

        const ws = new WebSocket(
            `${protocol}://${window.location.host}/ws/terminal`
        );

        wsRef.current = ws;

        ws.onopen = () => {
            terminal.write(
                "\r\n\x1b[32mConnected to terminal server\x1b[0m\r\n"
            );

            ws.send(
                JSON.stringify({
                    type: "terminal:open",
                    request_id: crypto.randomUUID(),
                    payload: {
                        agent_id: AGENT_ID,
                        cols: terminal.cols,
                        rows: terminal.rows,
                    },
                })
            );
        };

        ws.onmessage = (event) => {
            try {
                const message = JSON.parse(event.data);

                switch (message.type) {
                    case "terminal:opened": {
                        sessionIdRef.current =
                            message.payload?.session_id ?? null;

                        terminal.write(
                            "\r\n\x1b[32mTerminal session opened\x1b[0m\r\n"
                        );

                        break;
                    }

                    case "terminal:output": {
                        const data =
                            message.payload?.data ?? "";

                        terminal.write(data);

                        break;
                    }

                    case "terminal:exit": {
                        terminal.write(
                            `\r\n\x1b[33mProcess exited with code ${
                                message.payload?.code ?? 0
                            }\x1b[0m\r\n`
                        );

                        sessionIdRef.current = null;

                        break;
                    }

                    default: {
                        console.log(
                            "Terminal WebSocket message:",
                            message
                        );
                    }
                }
            } catch {
                // На случай, если сервер прислал обычный текст
                terminal.write(event.data);
            }
        };

        ws.onerror = () => {
            terminal.write(
                "\r\n\x1b[31mWebSocket error\x1b[0m\r\n"
            );
        };

        ws.onclose = () => {
            terminal.write(
                "\r\n\x1b[31mConnection closed\x1b[0m\r\n"
            );

            sessionIdRef.current = null;
        };

        terminal.onData((data) => {
            if (
                ws.readyState !== WebSocket.OPEN ||
                !sessionIdRef.current
            ) {
                return;
            }

            ws.send(
                JSON.stringify({
                    type: "terminal:input",
                    request_id: crypto.randomUUID(),
                    payload: {
                        session_id: sessionIdRef.current,
                        data,
                    },
                })
            );
        });

        const resize = () => {
            fitAddon.fit();

            if (
                ws.readyState !== WebSocket.OPEN ||
                !sessionIdRef.current
            ) {
                return;
            }

            ws.send(
                JSON.stringify({
                    type: "terminal:resize",
                    request_id: crypto.randomUUID(),
                    payload: {
                        session_id: sessionIdRef.current,
                        cols: terminal.cols,
                        rows: terminal.rows,
                    },
                })
            );
        };

        window.addEventListener("resize", resize);

        return () => {
            window.removeEventListener("resize", resize);

            const sessionId = sessionIdRef.current;

            if (
                sessionId &&
                ws.readyState === WebSocket.OPEN
            ) {
                ws.send(
                    JSON.stringify({
                        type: "terminal:close",
                        request_id: crypto.randomUUID(),
                        payload: {
                            session_id: sessionId,
                            reason: "terminal_unmounted",
                        },
                    })
                );
            }

            ws.close();
            terminal.dispose();
        };
    }, []);

    return (
        <section id="terminal" className="pages">
            <div
                ref={terminalRef}
                className="terminal_window"
                style={{
                    width: "100%",
                    height: "600px",
                }}
            />
        </section>
    );
}
