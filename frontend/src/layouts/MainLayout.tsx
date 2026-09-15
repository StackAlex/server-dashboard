import { Outlet, Link, useNavigate } from "react-router-dom";
import { api } from "../api/api";
import { thisUser } from "../api/user";
import { useState } from "react";
import {
  LayoutDashboard,
  Server,
  Logs,
  Terminal,
  Users,
  LogOut,
  Settings,
  HatGlasses,
} from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { LoadPage } from "../pages/LoadPage";

import docker from "../../public/docker-mark-ocean-blue.svg";


export default function MainLayout() {

    const navigate = useNavigate();
    const [errorLogin,setErrorLogin]=useState("")

    async function handleLogout(e: React.FormEvent) {
        e.preventDefault();
        setErrorLogin("")

        try{
            await api.post("/logout")
            navigate("/login");
        } catch (err){
            setErrorLogin((err as Error).message);
            console.log(errorLogin);
        }
    }
    const {
        data: user,
        isLoading,
        isError,
        error,
    } = useQuery({
        queryKey: ["user"],
        queryFn: thisUser,
        staleTime: 5 * 60 * 1000,
    });
    if (isError){
        console.error("error: ", error) 
    }
    return (
        <div className="main-layout">
            {isLoading ? (
                <LoadPage />
            ):(
                <>
                <div className="sidebar">
                    <h2>Dashboard <br/><a href="https://github.com/GWANUR" target="_blank" rel="noopener noreferrer">By StackAlex</a></h2>
                    <nav>
                        <ul>
                            <li>
                                <Link  to="/">
                                    <LayoutDashboard />
                                    Dashboard
                                </Link >
                            </li>
                            <li>
                                <Link to="/servers">
                                    <Server size={18} />
                                    Servers
                                </Link>
                            </li>
                            <li>
                                <Link to="/docker">
                                    <img src={docker} alt="Docker" width={18} height={18} />
                                    Docker
                                </Link>
                            </li>
                            <li>
                                <Link to="/log">
                                    <Logs size={18} />
                                    Log
                                </Link>
                            </li>
                            <li>
                                <Link to="/terminal">
                                    <Terminal size={18} />
                                    Terminal
                                </Link>
                            </li>
                            <li>
                                <Link to="/users">
                                    <Users size={18} />
                                    Users
                                </Link>
                            </li>
                            {user.type === "admin" && (
                                <li>
                                    <Link to="/agents">
                                        <HatGlasses size={18} />
                                        Agents
                                    </Link>
                                </li>
                            )}
                            <li>
                                <Link to="/settings">
                                    <Settings size={18} />
                                    Settings
                                </Link>
                            </li>
                        </ul>
                    <div className="logout btn_icon"
                        onClick={handleLogout}
                        >
                        <LogOut size={18}/>
                        Logout
                    </div>
                    </nav>
                </div>
                <main>
                    <Outlet />
                </main>
                </>
            )}
        </div>
    );
}