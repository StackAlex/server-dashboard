import { useEffect } from "react";

export default function Servers() {
    useEffect(() => {
        document.title = "Dashboard | Servers";
    }, []);
    return (
        <>
            <section id="servers" className="page">
                
            </section>
        </>
    );
}