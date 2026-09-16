import { useEffect, useRef, useState } from "react";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";
import "xterm/css/xterm.css";

const AGENT_ID = "100f0685-688b-46e9-b588-fcd3055865c7";

export default function Terminal_page() {
    const terminalRef = useRef<HTMLDivElement>(null);
    const wsRef = useRef<WebSocket | null>(null);
    const sessionIdRef = useRef<string | null>(null);

    const [command, setCommand] = useState("");
    const [connected, setConnected] = useState(false);

    const generateRequestId = () => {
        if (typeof crypto !== "undefined" && crypto.randomUUID) {
            return crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    };

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
            setConnected(true);

            terminal.write(
                "\r\n\x1b[32mConnected to terminal server\x1b[0m\r\n"
            );

            ws.send(
                JSON.stringify({
                    type: "terminal:open",
                    request_id: generateRequestId(),
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
                terminal.write(event.data);
            }
        };

        ws.onerror = () => {
            setConnected(false);

            terminal.write(
                "\r\n\x1b[31mWebSocket error\x1b[0m\r\n"
            );
        };

        ws.onclose = () => {
            setConnected(false);
            sessionIdRef.current = null;

            terminal.write(
                "\r\n\x1b[31mConnection closed\x1b[0m\r\n"
            );
        };

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
                    request_id: generateRequestId(),
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
                        request_id: generateRequestId(),
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

    const sendCommand = () => {
        const value = command;

        if (
            !value.trim() ||
            !wsRef.current ||
            wsRef.current.readyState !== WebSocket.OPEN ||
            !sessionIdRef.current
        ) {
            return;
        }

        wsRef.current.send(
            JSON.stringify({
                type: "terminal:input",
                request_id: generateRequestId(),
                payload: {
                    session_id: sessionIdRef.current,
                    data: value + "\n",
                },
            })
        );

        setCommand("");
    };

    const handleKeyDown = (
        event: React.KeyboardEvent<HTMLInputElement>
    ) => {
        if (event.key === "Enter") {
            event.preventDefault();
            sendCommand();
        }
    };

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

            <div
                style={{
                    display: "flex",
                    gap: "10px",
                    marginTop: "10px",
                    width: "100%",
                }}
            >
                <input
                    type="text"
                    value={command}
                    onChange={(event) =>
                        setCommand(event.target.value)
                    }
                    onKeyDown={handleKeyDown}
                    placeholder={
                        connected
                            ? "Введите команду..."
                            : "Терминал не подключен"
                    }
                    disabled={!connected}
                    style={{
                        flex: 1,
                        height: "40px",
                        padding: "0 12px",
                        boxSizing: "border-box",
                    }}
                />

                <button
                    type="button"
                    onClick={sendCommand}
                    disabled={
                        !connected ||
                        !command.trim() ||
                        !sessionIdRef.current
                    }
                    style={{
                        height: "40px",
                        padding: "0 20px",
                        cursor:
                            connected &&
                            command.trim() &&
                            sessionIdRef.current
                                ? "pointer"
                                : "default",
                    }}
                >
                    Отправить
                </button>
            </div>
        </section>
    );
}