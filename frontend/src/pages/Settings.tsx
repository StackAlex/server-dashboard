import { useEffect, useState } from "react";
import { getSettings } from "../api/settings";
import {
    ServerCog,
    UserRoundCog,
    UserPen,
    Save,
} from "lucide-react";
import ListSelect_By_StackAlex from "../components/ui/ListSelect_By_StackAlex/ListSelect_By_StackAlex";
import Toggle_By_StackAlex from '../components/ui/Toggle_By_StackAlex/Toggle_By_StackAlex';

interface ServerSetting {
    id: number;
    settings: string;
    value: string[] | null;
    type: "input" | "toggle" | "one_select" | "multi_select";
    options?: string[];
}

export default function Settings() {
    useEffect(() => {
        document.title = "Dashboard | Settings";
    }, []);

    const [settingsServer, setSettingsServer] = useState<ServerSetting[]>([]);
    const [settings, setSettings] = useState<Record<string, string[]>>({});

    useEffect(() => {
        const loadSettings = async () => {
            try {
                const response = await getSettings();

                setSettingsServer(response.data.settingsServer);

                // Заполняем локальное состояние текущими значениями
                const values: Record<string, string[]> = {};

                response.data.settingsServer.forEach((setting: ServerSetting) => {
                    values[setting.settings] = setting.value ?? [];
                });

                setSettings(values);
            } catch (error) {
                console.error("Failed to load settings:", error);
            }
        };

        loadSettings();
    }, []);

    const handleSettingChange = (
        settingName: string,
        value: string[]
    ) => {
        setSettings(prev => ({
            ...prev,
            [settingName]: value,
        }));
    };
    return (
        <section id="settings" className="page">
            <div className="window_all_settings">

                {/* USER SETTINGS */}
                <div className="settings_group">
                    <h2 className="title_icon">
                        <UserRoundCog size={45} />
                        User settings
                    </h2>

                    <form className="input_icon">
                        <label htmlFor="username">
                            Username
                        </label>

                        <input
                            id="username"
                            type="text"
                            placeholder="Username"
                        />

                        <br />

                        <label htmlFor="email">
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            placeholder="Email"
                        />

                        <br />

                        <button
                            type="submit"
                            className="btn_icon"
                        >
                            <Save size={18} />
                            Update
                        </button>
                    </form>
                </div>

                {/* PERSONAL SETTINGS */}
                <div className="settings_group">
                    <h2 className="title_icon">
                        <UserPen size={45} />
                        Personal settings
                    </h2>

                    <form className="input_icon">
                    </form>
                </div>

                {/* SERVER SETTINGS */}
                <div className="settings_group">
                    <h2 className="title_icon">
                        <ServerCog size={45} />
                        Server settings
                    </h2>

                    <form className="input_icon">

                        {settingsServer.map((setting) => {

                            const label = setting.settings
                                .split("_")
                                .join(" ");

                            return (
                                <div key={setting.id}>

                                    <label htmlFor={setting.settings}>
                                        {label}
                                    </label>

                                    {/* INPUT */}
                                    {setting.type === "input" && (
                                        <input
                                            id={setting.settings}
                                            name={setting.settings}
                                            type="text"
                                            value={settings[setting.settings]?.[0] ?? ""}
                                            onChange={(e) =>
                                                handleSettingChange(
                                                    setting.settings,
                                                    [e.target.value]
                                                )
                                            }
                                        />
                                    )}
                                    {/* TOGGLE */}
                                    {setting.type === "toggle" && (
                                        <Toggle_By_StackAlex
                                            value={settings[setting.settings]?.[0] === "true"}
                                            onChange={(value) =>
                                                handleSettingChange(
                                                    setting.settings,
                                                    [value ? "true" : "false"]
                                                )
                                            }
                                        />
                                    )}
                                    {setting.type === "multi_select" && (
                                        <ListSelect_By_StackAlex
                                            list={setting.options ?? []}
                                            MultiSelect={true}
                                            value={settings[setting.settings] ?? []}
                                            onChange={(value) =>
                                                handleSettingChange(
                                                    setting.settings,
                                                    value
                                                )
                                            }
                                        />
                                    )}

                                    {setting.type === "one_select" && (
                                        <ListSelect_By_StackAlex
                                            list={setting.options ?? []}
                                            value={settings[setting.settings] ?? []}
                                            onChange={(value) =>
                                                handleSettingChange(
                                                    setting.settings,
                                                    value
                                                )
                                            }
                                        />
                                    )}
                                </div>
                            );
                        })}
                        <button
                            type="submit"
                            className="btn_icon"
                        >
                            <Save size={18} />
                            Update
                        </button>
                    </form>
                </div>

            </div>
        </section>
    );
}