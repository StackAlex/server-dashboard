import { RotateCw,
        SquarePlus,
        Pencil,
        Trash2,
        Pause,
        PenOff,
        Square,
        Play,
 } from "lucide-react";

import {useEffect, useState}from 'react';
import {CheckBox_By_StackAlex} from "../components/ui/CheckBox_By_StackALex/CheckBox_By_StackAlex";
import { allContainers } from "../api/docker";

export default async function Docker_page() {

    useEffect(() => {
        document.title = "Dashboard | Docker";

    }, []);

    const dataContainers = await allContainers();

    console.log(dataContainers);

    const [editCon, setEditCon] = useState(false);

    return (
        <section className="page" id="docker">
            <h1>Docker</h1>
            <div className="docker_window">
                <h2>Docker Page</h2>
                <div className="docker_actions">
                    <button className="docker_action btn_icon" 
                        onClick={()=>setEditCon(!editCon)}
                    >
                        {editCon ? (<><PenOff size={18}/>Cancel</>) : (<><Pencil size={18}/>Edit</>)}
                    </button>
                    <button className="docker_action btn_icon" >
                        <RotateCw size={18}/>
                        Update
                    </button>
                    <button className="docker_action btn_icon" >
                        <SquarePlus size={18}/>
                        Add Container
                    </button>
                    <button className="docker_action btn_icon" >
                        <Square size={18}/>
                        Stop
                    </button>
                    <button className="docker_action btn_icon" >
                        <Pause size={18}/>
                        Pause
                    </button>
                    <button className="docker_action btn_icon" >
                        <Play size={18}/>
                        Run
                    </button>
                    {editCon && (
                        <>
                            <button className="docker_action btn_icon" >
                                <RotateCw size={18}/>
                                Update
                            </button>
                            <button className="docker_action btn_icon" >
                                <Trash2 size={18}/>
                                Remove
                            </button>
                        </>
                    )}

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
                                {/* <CheckBox_By_StackAlex
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