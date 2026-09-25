// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css
// =====================================

import { useState } from "react";
import "./index.css";

interface ToggleProps {
    size?: number;
    colorEnable?: string;
    colorDisable?: string;
    value?: boolean;
    onChange?: (value: boolean) => void;
}

export default function Toggle_By_StackAlex({
    size = 40,
    colorEnable,
    colorDisable,
    value = false,
    onChange,
}: ToggleProps) {

    return (
        <div
            className="Toggle_By_StackAlex"
            onClick={() => onChange?.(!value)}
            style={{
                background: value
                    ? colorEnable
                    : colorDisable,
                width: size,
            }}
        >
            <div className="toggle_el">

                <div
                    className="active_bg"
                    style={{
                        background: colorEnable,
                        opacity: value ? 1 : 0,
                    }}
                ></div>

                <div
                    className="around"
                    style={{
                        transform: value
                            ? "translateX(100%)"
                            : "translateX(0)",
                    }}
                ></div>

            </div>
        </div>
    );
}