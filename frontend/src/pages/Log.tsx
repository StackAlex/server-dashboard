import { Search } from "lucide-react";
import { useEffect } from "react";

export default function Log() {
    useEffect(() => {
        document.title = "Dashboard | Log";
    }, []);
    const log = [
        {
            Timestamp: "2023-06-01 12:00:00",
            type: "Info",
            message:"Server started"
        },
        {
            Timestamp: "2023-06-01 12:00:00",
            type: "Info",
            message:"Server started"
        },
        {
            Timestamp: "2023-06-01 12:00:00",
            type: "Info",
            message:"Server started"
        },
        {
            Timestamp: "2023-06-01 12:00:00",
            type: "Info",
            message:"Server started"
        }
    ];
    return (
        <>
            <section id="log" className="pages">
                <div className="log_window">
                    <div className="search">
                        <input type="text" className="" placeholder="Search logs..." />
                        <button className="btn_icon">
                            <Search size={20} color="#fff"/>
                        </button>
                    </div>
                    <pre>
                        <table className="log_table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Message</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                {log.map((entry, index) => (
                                    <tr key={index}>
                                        <td>{entry.type}</td>
                                        <td>{entry.message}</td>
                                        <td>{entry.Timestamp}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </pre>
                </div>
            </section>
        </>
    );
}