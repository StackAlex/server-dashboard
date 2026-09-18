import { RotateCw,
        SquarePlus,
        Pencil,
        Trash2
 } from "lucide-react";

export default function Docker_page() {
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
                <table className="table_containers">
                    <span className="tablename">📦 Containers</span>
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
                            <td></td>
                            <td>Container 1</td>
                            <td>Image 1</td>
                            <td>Running</td>
                            <td>8080</td>
                            <td>
                                <button className="docker_action btn_icon" >
                                    <Pencil size={18}/>
                                    Edit
                                </button>
                                <button className="docker_action btn_icon" >
                                    Stop
                                </button>
                                <button className="docker_action" >
                                    <Trash2 size={18}/>
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    );
}