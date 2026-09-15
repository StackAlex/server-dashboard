import { Command, CornerDownLeft } from "lucide-react";
import { useState, useRef } from "react";
import { Terminal } from "xterm";
import { FitAddon } from "xterm-addon-fit";


export default function Terminal_page() {
    const [command, setCommand] = useState("");
    function sendCommand(cmd: string) {
        // Here you can implement the logic to send the command to the server or handle it as needed.
        console.log("CMD:", cmd);
        
        setCommand(""); // Clear the input after sending the command
    }
    return (
        <section id="terminal" className="pages">
            <div className="terminal_window">
                <pre></pre>
                <div className="input_icon">
                    <input
                        type="text"
                        value={command}
                        placeholder="Enter command..."
                        onChange={(e) => setCommand(e.target.value)}
                    />
                    <button className="btn" onClick={() => sendCommand(command)}>
                        <CornerDownLeft size={20} color="#fff" />
                    </button>
                </div>
            </div>
        </section>
    );
}