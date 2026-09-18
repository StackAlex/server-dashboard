import {
  SmilePlus,
  Pencil,
  PencilOff,
  Trash,
  Search,
  Save,
  CircleCheck,
  CircleX
} from "lucide-react";
import { useState, useEffect } from "react";
import { LoadPage } from "./LoadPage";
import { thisUser } from "../api/user"
import { allAgent, saveAgent, deleteAgents } from "../api/agent"
import { SecretInput } from "../components/ui/SecretInput_By_StackAlex/SecretInput"
import type { Agent } from "../api/agent"
import { useQuery } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { CheckBox_SA } from "../components/ui/CheckBox_By_StackALex/CheckBoxSA";


export default function AgentPage(){
    
    const navigate = useNavigate();
    const {
        data: user,
        isLoading: isUserLoading,
        isError: isUserError,
        error: userError,
    } = useQuery({
        queryKey: ["user"],
        queryFn: thisUser,
    });

    const {
        data: agents,
        isLoading: isAgentsLoading,
    } = useQuery({
        queryKey: ["agents"],
        queryFn: allAgent,
        enabled: user?.type === "admin",
    });

    useEffect(() => {
        if (!isUserLoading && user?.type !== "admin") {
            navigate("/");
        }
    }, [isUserLoading, user, navigate]);

    const [edit, setEdit] = useState(false);
    const [popup, setPopup] = useState(false);
    function handleEdit(stat:boolean) {
        if (stat) {
            setEdit(false)
        } else {
            setEdit(true)
        }
    }

    const [agentName, setAgentName] = useState("");
    const [agentToken, setAgentToken] = useState("");

    async function handleSaveAgent() {
        try { 
            const data = await saveAgent(agentName, agentToken);

            console.log(data);

        } catch (error) {
            console.error(error);
        }
    }

    const [selectedAgents, setSelectedAgents] = useState<number[]>([]);
    function toggleAgent(id: number, checked: boolean) {
        if (checked) {
            setSelectedAgents(prev => [...prev, id]);
        } else {
            setSelectedAgents(prev => prev.filter(agentId => agentId !== id));
        }
    }
    async function getDeletAgents() {
        try {
            console.log(selectedAgents);

            await deleteAgents(selectedAgents);

        } catch (error) {
            console.error(error);
        }
    }

    return (
        <section id="agents" className="page">
            {popup && (
                <div id="popup">
                    <div className="popup_window">
                        <div className="phead">
                            <span>Agent</span>
                            <button onClick={() => setPopup(false)}>x</button>
                        </div>
                        <div className="pbody">
                            <div className="inputGroup">
                                <label>Name:</label>
                                <input type="text" name="nameAgent" className="input_icon"
                                    value={agentName} onChange={(e)=>{
                                        setAgentName(e.target.value);
                                    }}
                                /> 
                            </div>
                            <div className="inputGroup">
                                <label>Token:</label>
                                <SecretInput nameInput="agentToken" value={agentToken} onChange={setAgentToken}/>
                            </div>
                            <button
                            onClick={() => handleSaveAgent()}>Save <Save size={16}/></button>
                        </div>
                    </div>
                </div>
            )}
            <div className="window_table_agents">
                <div className="table_head">
                    <div className="search_agent input_icon">
                        <input type="text" name="agent_token"></input>
                        <Search size={20} />
                    </div>
                    <div className="active">
                        <button className="btn_icon"
                        onClick={() => setPopup(true)}>
                            <SmilePlus size={18}/>
                        </button>
                        {!edit ?(
                            <button className="btn_icon"
                                onClick={()=>handleEdit(edit)}>
                                <Pencil size={18}/>
                            </button>
                        ) : (
                            <>
                                <button className="btn_icon"
                                    onClick={()=>{getDeletAgents();}}>
                                    <Trash size={18}/>
                                </button>
                                <button className="btn_icon"
                                    onClick={()=>handleEdit(edit)}>
                                    <PencilOff size={18}/>
                                </button>
                            </>
                        )}
                    </div>
                </div>
                {isUserLoading || isAgentsLoading ? (
                    <LoadPage/>
                ) : (
                    <table>
                        <thead>
                            <tr>
                                {edit && (
                                    <th></th>
                                )}
                                <th>UUID</th>
                                <th>Name</th>
                                <th>Owner</th>
                                <th>Add at</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {agents.allAgents?.map((el: Agent) => (
                                <tr key={el.id}>
                                    {edit && (
                                        <td>
                                            <CheckBox_SA
                                                nameCheckBox={`agent-${el.id}`}
                                                checked={selectedAgents.includes(el.id)}
                                                onChange={(checked) => toggleAgent(el.id, checked)}
                                            />
                                        </td>
                                    )}
                                    <td>{el.agent_id || null}</td>
                                    <td>{el.name}</td>
                                    <td>{el.user_id}</td>
                                    <td>{el.created_at}</td>
                                    <td>{(el.enabled) ? <CircleCheck size={15} color="green" /> : <CircleX size={15} color="red" /> }</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </section>
    )
}