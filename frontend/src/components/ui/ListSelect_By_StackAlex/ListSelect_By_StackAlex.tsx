// =====================================
// This component created by StackAlex
// https://github.com/StackAlex
// Rules:
// 1. You cannot delete this comment
// 2. You cannot rename classes in this file, only add classes
// 3. You can edit file index.css
// =====================================

import { useEffect, useRef, useState } from "react";
import "./index.css";

interface ListSelectProps {
    list: any[];
    itemOptions?: string;
    itemName?: string;
    MultiSelect?: boolean;
    value?: any | any[];
    onChange?: (selected: any | any[]) => void;
}

export default function ListSelect_By_StackAlex({
    list,
    itemName,
    itemOptions,
    MultiSelect = false,
    value,
    onChange
}: ListSelectProps) {

    console.log(
        "This website use ListSelect By https://github.com/StackAlex"
    );

    const [listSelect, setListSelect] = useState<Record<string, any>[]>([]);
    const [isOpen, setIsOpen] = useState(false);

    const selectRef = useRef<HTMLDivElement>(null);
    function getItemLabel(item: any) {
        if (itemName) {
            return item[itemName];
        }

        if (typeof item === "object" && item !== null) {
            return item.label ?? item.value ?? "";
        }

        return item;
    }
    /*
     * Синхронизация выбранных элементов
     * с value, который пришёл извне.
     */
    useEffect(() => {
        if (value === undefined) {
            return;
        }

        if (MultiSelect) {
            const values = Array.isArray(value) ? value : [value];

            const selected = list.filter(option => {
                const optionValue = itemOptions
                    ? option[itemOptions]
                    : option;

                return values.some(
                    selectedValue => String(selectedValue) === String(optionValue)
                );
            });

            setListSelect(selected);
        } else {
            const selectedValue = Array.isArray(value)
                ? value[0]
                : value;

            const selected = list.find(option => {
                const optionValue = itemOptions
                    ? option[itemOptions]
                    : option;

                return String(selectedValue) === String(optionValue);
            });

            setListSelect(selected ? [selected] : []);
        }
    }, [value, list, itemOptions, MultiSelect]);

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

            setIsOpen(false);
        }
    }

    useEffect(() => {

        function handleClickOutside(event: MouseEvent) {

            if (
                selectRef.current &&
                !selectRef.current.contains(event.target as Node)
            ) {
                setIsOpen(false);
            }

        }

        document.addEventListener("mousedown", handleClickOutside);

        return () => {
            document.removeEventListener(
                "mousedown",
                handleClickOutside
            );
        };

    }, []);

    return (
        <div
            ref={selectRef}
            className={`ListInput_By_StackAlex ${
                MultiSelect ? "MoreSelect" : "SoloSelect"
            } ${isOpen ? "isOpen" : ""}`}
        >

            {/* HEADER */}

            <div
                className="SoloSelectHeader"
                onClick={() => setIsOpen(prev => !prev)}
            >

                <div className="select_item">

                    {listSelect.length > 0 ? (

                        listSelect.map((item, index) => (
                            <span
                                className="selectItem"
                                key={index}
                            >
                                {getItemLabel(item)}
                            </span>
                        ))

                    ) : (

                        <span className="selectPlaceholder">
                            Select...
                        </span>

                    )}

                </div>

                <button
                    type="button"
                    className="selectArrow"
                    aria-label="Open select"
                >
                    <span>⌄</span>
                </button>

            </div>

            {/* OPTIONS */}

            <ul className={isOpen ? "open" : ""}>

                {list.map((option, index) => {

                    const selected = listSelect.includes(option);

                    return (
                        <li
                            key={index}
                            className={selected ? "selected" : ""}
                            onClick={() => hundleSelect(option)}
                        >

                            <span className="optionText">
                                {itemName
                                    ? option[itemName]
                                    : option
                                }
                            </span>

                            {selected && (
                                <span className="optionCheck">
                                    ✓
                                </span>
                            )}

                        </li>
                    );
                })}

            </ul>

        </div>
    );
}