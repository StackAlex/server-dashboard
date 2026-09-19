import { useEffect, useRef, useState } from "react";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";
import "xterm/css/xterm.css";

const AGENT_ID = "100f0685-688b-46e9-b588-fcd3055865c7";

export default function Terminal_page() {
    const terminalRef = useRef<HTMLDivElement>(null);
    const wsRef = useRef<WebSocket | null>(null);
    const sessionIdRef = useRef<string | null>(null);

    const [connected, setConnected] = useState(false);

    const generateRequestId = () => {
        if (
            typeof crypto !== "undefined" &&
            crypto.randomUUID
        ) {
            return crypto.randomUUID();
        }

        return `${Date.now()}-${Math.random()
            .toString(36)
            .slice(2)}`;
    };

    useEffect(() => {
        if (!terminalRef.current) {
            return;
        }

        const terminal = new Terminal({
            cursorBlink: true,
            fontSize: 14,
            fontFamily: "monospace",

            // xterm сам работает с \r\n и ANSI escape sequences
            convertEol: false,

            scrollback: 5000,

            allowProposedApi: true,
        });

        const fitAddon = new FitAddon();

        terminal.loadAddon(fitAddon);
        terminal.open(terminalRef.current);

        fitAddon.fit();

        const protocol =
            window.location.protocol === "https:"
                ? "wss"
                : "ws";

        const ws = new WebSocket(
            `${protocol}://${window.location.host}/ws/terminal`
        );

        wsRef.current = ws;

        /*
         * WebSocket подключился
         */
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

        /*
         * Получаем сообщения от Laravel
         */
        ws.onmessage = (event) => {
            try {
                const message = JSON.parse(event.data);

                switch (message.type) {
                    /*
                     * PTY создан
                     */
                    case "terminal:opened": {
                        sessionIdRef.current =
                            message.payload?.session_id ?? null;

                        terminal.write(
                            "\r\n\x1b[32mTerminal session opened\x1b[0m\r\n"
                        );

                        terminal.focus();

                        break;
                    }

                    /*
                     * Вывод PTY
                     */
                    case "terminal:output": {
                        const data =
                            message.payload?.data ?? "";

                        terminal.write(data);

                        break;
                    }

                    /*
                     * PTY завершился
                     */
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

        /*
         * WebSocket ошибка
         */
        ws.onerror = () => {
            setConnected(false);

            terminal.write(
                "\r\n\x1b[31mWebSocket error\x1b[0m\r\n"
            );
        };

        /*
         * WebSocket закрыт
         */
        ws.onclose = () => {
            setConnected(false);
            sessionIdRef.current = null;

            terminal.write(
                "\r\n\x1b[31mConnection closed\x1b[0m\r\n"
            );
        };

        /*
         * Ввод пользователя в xterm
         *
         * Сюда попадает каждый символ:
         *
         * a
         * b
         * c
         * Enter -> \r
         * Ctrl+C -> \x03
         * Backspace -> \x7f
         * стрелки -> ANSI escape sequences
         */
        const dataDisposable = terminal.onData((data) => {
            if (
                ws.readyState !== WebSocket.OPEN ||
                !sessionIdRef.current
            ) {
                return;
            }

            ws.send(
                JSON.stringify({
                    type: "terminal:input",
                    request_id: generateRequestId(),

                    payload: {
                        session_id:
                            sessionIdRef.current,

                        data,
                    },
                })
            );
        });

        /*
         * Изменение размера терминала
         */
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
                        session_id:
                            sessionIdRef.current,

                        cols: terminal.cols,
                        rows: terminal.rows,
                    },
                })
            );
        };

        window.addEventListener("resize", resize);

        /*
         * Очистка
         */
        return () => {
            window.removeEventListener(
                "resize",
                resize
            );

            dataDisposable.dispose();

            const sessionId =
                sessionIdRef.current;

            if (
                sessionId &&
                ws.readyState === WebSocket.OPEN
            ) {
                ws.send(
                    JSON.stringify({
                        type: "terminal:close",
                        request_id:
                            generateRequestId(),

                        payload: {
                            session_id:
                                sessionId,

                            reason:
                                "terminal_unmounted",
                        },
                    })
                );
            }

            ws.close();

            terminal.dispose();

            wsRef.current = null;
            sessionIdRef.current = null;
        };
    }, []);

    return (
        <section
            id="terminal"
            className="pages"
            style={{
                width: "100%",
            }}
        >
            <div
                ref={terminalRef}
                className="terminal_window"
                style={{
                    width: "100%",
                    height: "95%",
                    overflow: "hidden",
                }}
                onClick={(event) => {
                    event.currentTarget
                        .querySelector(".xterm")
                        ?.dispatchEvent(
                            new MouseEvent("mousedown", {
                                bubbles: true,
                            })
                        );
                }}
            />

            <div
                style={{
                    marginTop: "10px",
                    fontSize: "13px",
                    color: connected
                        ? "#4ade80"
                        : "#f87171",
                }}
            >
                {connected
                    ? "● Connected"
                    : "● Disconnected"}
            </div>
        </section>
    );
}