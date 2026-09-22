import { useEffect, useState } from "react";
import { api } from "../api/api";
import { ServerCog,
    UserRoundCog,
    UserPen,
    Save,
    SaveCheck
 } from "lucide-react";

export default function Settings() {
    const [settingsInfo, setSettingsInfo] = useState("");
    useEffect(() => {
        document.title = "Dashboard | Settings";
    }, []);
    // function getSettings(){
    //     try{
    //         api
    //     }
    // }
    return (
        <>
            <section id="settings" className="page">
                <div className="window_all_settings">
                    <div className="settings_group">
                        <h2><UserRoundCog size={18}/>User settings</h2> 
                        <form action="" className="input_icon">
                            <label htmlFor="username">Username</label>
                            <input type='text' placeholder={`Username`}/>
                            <br></br>
                            <label htmlFor="email">Email</label>
                            <input type='email' placeholder={`Email`}/>
                            <br></br>
                            <button className="btn_icon" >
                                <Save size={18}/>
                                Update
                            </button>
                        </form>
                    </div>
                    <div className="settings_group">
                        <h2><UserPen size={18}/>Personal settings</h2> 
                        <form action="" className="input_icon">
                        </form>
                    </div>
                    <div className="settings_group">
                        <h2><ServerCog size={18}/>Server settings</h2> 
                        <form action="" className="input_icon">
                        </form>
                    </div>
                </div>
            </section>
        </>
    );
}