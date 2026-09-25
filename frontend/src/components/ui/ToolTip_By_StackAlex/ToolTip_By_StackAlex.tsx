// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css 
// =====================================

import {
    useRef,
    useState
} from "react";

import type { ReactNode } from "react";

interface ToolTipProps {
    children: ReactNode;
    type: "info" | "fulltext";
    text?: string;
}

export default function ToolTip_By_StackAlex({
    children,
    type,
    text,
}: ToolTipProps) {

    const [visible, setVisible] = useState(false);
    const [fullText, setFullText] = useState("");

    const contentRef = useRef<HTMLSpanElement>(null);

    const handleMouseEnter = () => {
        if (type === "fulltext" && contentRef.current) {
            setFullText(contentRef.current.textContent || "");
        }

        setVisible(true);
    };

    return (
        <span
            className="ToolTip_By_StackAlex"
            onMouseEnter={handleMouseEnter}
            onMouseLeave={() => setVisible(false)}
        >
            <span ref={contentRef}>
                {children}
            </span>

            {visible && (
                <span className={`tooltip tooltip-${type}`}>
                    {type === "info" ? text : fullText}
                </span>
            )}
        </span>
    );
}
// 



/* 
===================================================================
Type: info
Example:
<ToolTip_By_StackAlex
    type="info"
    text="Open settings"
>
    <button>⚙</button>
</ToolTip_By_StackAlex>
===================================================================
Type: fulltext
Example:
<ToolTip_By_StackAlex type="fulltext">
    <span>
        ServerAlex Production Minecraft Server
    </span>
</ToolTip_By_StackAlex>
===================================================================
*/


