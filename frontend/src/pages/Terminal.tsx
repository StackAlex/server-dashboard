import { useEffect, useRef } from "react";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";
import "xterm/css/xterm.css";

export default function Terminal_page() {
    const terminalRef = useRef<HTMLDivElement>(null);
    const wsRef = useRef<WebSocket | null>(null);

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

        const ws = new WebSocket(
            `ws://${window.location.host}/ws/terminal`
        );

        wsRef.current = ws;

        ws.onopen = () => {
            terminal.write("\r\n\x1b[32mConnected to server\x1b[0m\r\n");
        };

        ws.onmessage = (event) => {
            terminal.write(event.data);
        };

        ws.onerror = () => {
            terminal.write("\r\n\x1b[31mWebSocket error\x1b[0m\r\n");
        };

        ws.onclose = () => {
            terminal.write("\r\n\x1b[31mConnection closed\x1b[0m\r\n");
        };

        terminal.onData((data) => {
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(
                    JSON.stringify({
                        type: "input",
                        data,
                    })
                );
            }
        });

        const resize = () => {
            fitAddon.fit();

            if (ws.readyState === WebSocket.OPEN) {
                ws.send(
                    JSON.stringify({
                        type: "resize",
                        cols: terminal.cols,
                        rows: terminal.rows,
                    })
                );
            }
        };

        window.addEventListener("resize", resize);

        return () => {
            window.removeEventListener("resize", resize);

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