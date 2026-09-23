// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css 
// =====================================

import { useState } from "react";

interface ListSelectProps {
    list: Record<string, any>[];
    itemOptions?: string;
    itemName?: string;
    MultiSelect?: boolean;
    onChange?: (selected: any | any[]) => void;
}

export default function ListSelect_By_StackAlex({
    list,
    itemName,
    itemOptions,
    MultiSelect = false,
    onChange
}: ListSelectProps) {

    const [listSelect, setListSelect] = useState<Record<string, any>[]>([]);

    function hundleSelect(option: Record<string, any>) {

        if (MultiSelect) {

            setListSelect(prev => {
                const newList = prev.includes(option)
                    ? prev.filter(item => item !== option)
                    : [...prev, option];

                onChange?.(
                    itemOptions
                        ? newList.map(item => item[itemOptions])
                        : newList
                );

                return newList;
            });

        } else {

            setListSelect([option]);

            onChange?.(
                itemOptions
                    ? option[itemOptions]
                    : option
            );
        }
    }

    return (
        <div
            className={`ListInput_By_StackAlex ${
                MultiSelect ? "MoreSelect" : "SoloSelect"
            }`}
        >

            <div className="select_item">

                {listSelect.map((item, index) => (
                    <span
                        className="selectItem"
                        key={index}
                    >
                        {itemName
                            ? item[itemName]
                            : item
                        }
                    </span>
                ))}

            </div>

            <ul>
                {list.map((option, index) => (
                    <li
                        key={index}
                        onClick={() => hundleSelect(option)}
                    >
                        {itemName
                            ? option[itemName]
                            : option
                        }
                    </li>
                ))}
            </ul>

        </div>
    );
}