// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css 
// =====================================


import { useState } from "react";
import {
  Eye,
  EyeOff
} from "lucide-react";
import "./index.css";

interface SecretInputProps {
    nameInput: string;
    value: string;
    onChange: (value: string) => void;
}
export function SecretInput_By_StackAlex({ nameInput, value, onChange }: SecretInputProps){
    console.log("This website use SecretInput By https://github.com/StackAlex")
    const [hidden,setHidden] = useState(true)
    return (
        <div className="SecretInput__SA">
            <input 
                name={nameInput} 
                type={hidden ? "password" : "text"}
                autoComplete="off"
                spellCheck={false}
                autoCapitalize="off"
                value={value ?? ""}
                onChange={(e) => onChange?.(e.target.value)}
            />
            <button
                type="button"
                onClick={()=> setHidden(!hidden)}
            >
                { hidden ? (
                    <Eye />
                    ) : (
                    <EyeOff />
                    )
                }
            </button>
        </div>
    )
}