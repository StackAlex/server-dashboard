import { useEffect, useState } from "react";
import { api } from "../api/api";
import { getSettings } from "../api/settings"
import { ServerCog,
    UserRoundCog,
    UserPen,
    Save,
    SaveCheck,
    User
 } from "lucide-react";

export default function Settings() {
    useEffect(() => {
        document.title = "Dashboard | Settings";
    }, []);
    const [settingsInfo, setSettingsInfo] = useState([]);

    const settingsServer = getSettings()
    
    return (
        <>
            <section id="settings" className="page">
                <div className="window_all_settings">
                    <div className="settings_group">
                        <h2 className="title_icon"><UserRoundCog size={45}/>User settings</h2> 
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
                        <h2 className="title_icon"><UserPen size={45}/>Personal settings</h2> 
                        <form action="" className="input_icon">
                        </form>
                    </div>
                    <div className="settings_group">
                        <h2 className="title_icon"><ServerCog size={45}/>Server settings</h2> 
                        <form action="" className="input_icon">
                        </form>
                    </div>
                </div>
            </section>
        </>
    );
}