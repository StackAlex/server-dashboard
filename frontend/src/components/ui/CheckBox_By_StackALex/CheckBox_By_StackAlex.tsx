// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css 
// =====================================

import "./index.css";

interface CheckBoxProps {
    nameCheckBox: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}

export function CheckBox_By_StackAlex({
    nameCheckBox,
    checked,
    onChange
}: CheckBoxProps) {
    console.log("This website use CheckBox By https://github.com/StackAlex")
    return (
        <label className="CheckBox__SA">
            <input
                type="checkbox"
                name={nameCheckBox}
                id={nameCheckBox}
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
            />
        </label>
    );
}

/* 
===================================================================
Example:

toggleAgent - function
const [selectedAgents, setSelectedAgents] = useState<number[]>([]);

<CheckBox_By_StackAlex
    nameCheckBox={`agent-${el.id}`}
    checked={selectedAgents.includes(Number(el.id))}
    onChange={(checked) =>
        toggleAgent(Number(el.id), checked)
    }
/>
===================================================================
*/