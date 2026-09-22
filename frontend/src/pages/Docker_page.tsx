import { RotateCw,
        SquarePlus,
        Pencil,
        Trash2,
        Pause,
 } from "lucide-react";

import {useEffect}from 'react';
import {CheckBox_SA} from "../components/ui/CheckBox_By_StackALex/CheckBoxSA";

export default function Docker_page() {
    useEffect(() => {
        document.title = "Dashboard | Docker";
    }, []);
    return (
        <section className="page" id="docker">
            <h1>Docker</h1>
            <div className="docker_window">
                <h2>Docker Page</h2>
                <div className="docker_actions">
                    <button className="docker_action btn_icon" >
                        <RotateCw size={18}/>
                        Update
                    </button>
                    <button className="docker_action btn_icon" >
                        <SquarePlus size={18}/>
                        Add Container
                    </button>
                    <button className="docker_action btn_icon" >
                        <SquarePlus size={18}/>
                        Stop
                    </button>
                    <button className="docker_action btn_icon" >
                        <Trash2 size={18}/>
                        Remove
                    </button>
                    <button className="docker_action btn_icon" >
                        <Pencil size={18}/>
                        Edit
                    </button>
                </div>
                <h3 className="table_title">
                    <span className="tablename">📦 Containers</span>
                </h3>
                <table className="table_containers">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Container</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Ports</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {/* <CheckBox_SA
                                    nameCheckBox={`container-${cont.id}`}
                                    checked={selectedAgents.includes(cont.id)}
                                    onChange={(checked) => toggleContainer(cont.id, checked)}
                                /> */}
                            </td>
                            <td>Container 1</td>
                            <td>Image 1</td>
                            <td>Running</td>
                            <td>8080</td>
                            <td>
                                <div className="docker_actions_container">
                                    <button className="docker_action btn_icon" >
                                        <Pencil size={18}/>
                                    </button>
                                    <button className="docker_action btn_icon" >
                                        <Pause size={18}/>
                                    </button>
                                    <button className="docker_action btn_icon" >
                                        <Trash2 size={18}/>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    );
}