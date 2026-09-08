import {
  Cpu,
  MemoryStick,
  HardDrive,
  Network,
  CircleCheck,
  Play,
  Power,
  RotateCcw
} from "lucide-react";

import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    Tooltip,
    CartesianGrid,
    ResponsiveContainer,
    Pie,
    PieChart,
    Cell,
} from "recharts";
import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { agent } from "../api/agent";
import { thisUser } from "../api/user"
import { LoadPage } from "./LoadPage";

const COLORS = ["#8b5cf6", "#06b6d4", "#22c55e"];

type Stats = {
    cpu?: {
        usage: number;
    };
    memory?: {
        total_gb: number;
        used_gb: number;
        percent: number;
    };
    disk?: {
        total_gb: number;
        used_gb: number;
        percent: number;
    };
    network?: {
        in_bytes?: number;
        network_in?: number;
    };
    load?: {
        one_minute: number;
        five_minutes: number;
        fifteen_minutes: number;
    };
    uptime?: string;
};

export default function Dashboard() {
    const [stats, setStats] = useState<Stats>({});
    const { data: agentStats } = useQuery({
        queryKey: ["agent-stats"],
        queryFn: agent,
        refetchInterval: 2000,
        staleTime: 1000,
    });

    const {
        data: user,
        isLoading,
        error,
    } = useQuery({
        queryKey: ["user"],
        queryFn: thisUser,
        staleTime: 5 * 60 * 1000,
    });

    type CpuPoint = {
        time: string;
        usage: number;
    };

    const [cpuHistory, setCpuHistory] = useState<CpuPoint[]>([]);

    useEffect(() => {
        if (!agentStats) return;

        const nextStats = agentStats as Stats;
        setStats(nextStats);
        setCpuHistory((history) => [
            ...history.slice(-29),
            { time: new Date().toLocaleTimeString(), usage: nextStats.cpu?.usage ?? 0 },
        ]);
    }, [agentStats]);

    const ramData = useMemo(() => [
        { name: "Used", value: stats.memory?.percent ?? 0 },
        { name: "Free", value: Math.max(100 - (stats.memory?.percent ?? 0), 0) },
    ], [stats.memory?.percent]);
    return (
        <section id="dashboard" className="page">
            {isLoading ? (
                <LoadPage />
            ):(
                <>
                    <header>
                        <h2>Welcome back, { user?.name }!</h2>
                        <div className="rightheader">
                            <div className="server_controll">
                                <div className="btn_icon"><Play size={18} color="#fff" /></div>
                                <div className="btn_icon"><Power size={18} color="#fff" /></div>
                                <div className="btn_icon"><RotateCcw size={18} color="#fff" /></div>
                            </div>
                            <div className="server_active">
                                <span className="label">Server:</span>
                                <span className="value">Online<CircleCheck size={18} color="green" /></span>
                            </div>
                        </div>
                    </header>
                    <div className="systemstat">
                        <h2>Server Status</h2>
                        <div className="all_system">
                            <div className="cpu_stat">
                                <Cpu size={60} className="icon" />
                                <span className="label">CPU Usage:</span>
                                <span className="value">{`${stats.cpu?.usage ?? 0}%`}</span>
                            </div>
                            <div className="ram_stat">
                                <MemoryStick size={60} className="icon" />
                                <span className="label">RAM Usage:</span>
                                <span className="value">{`${stats.memory?.percent ?? 0}%`}</span>
                            </div>
                            <div className="disk_stat">
                                <HardDrive size={60} className="icon" />
                                <span className="label">Disk Usage:</span>
                                <span className="value">{`${stats.disk?.used_gb ?? 0}/${stats.disk?.total_gb ?? 0} GB`}</span>
                            </div>
                            <div className="network_stat">
                                <Network size={60} className="icon" />
                                <span className="label">Network speed:</span>
                                <span className="value">{`${stats.network?.in_bytes ?? stats.network?.network_in ?? 0} bytes`}</span>
                            </div>
                        </div>
                        <hr />
                    </div>
                    <div className="services_stats">
                        <h2>Services Status</h2>
                        <div className="all_services">
                            <div className="running">
                                <span className="label">Load avg:</span>
                                <span className="value">{`${stats.load?.one_minute ?? 0}`}</span>
                            </div>
                            <div className="active_containers">
                                <span className="label">Uptime:</span>
                                <span className="value">{stats.uptime ?? 0}</span>
                            </div>
                        </div>
                        <hr />
                    </div>
                    <div className="graph">
                        <h2>Server Performance</h2>
                        <div className="all_graph">
                            <div className="cpugraph">
                                <span className="label">CPU Usage</span>
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={cpuHistory}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="time" />
                                        <YAxis domain={[0, 100]} />
                                        <Tooltip />
                                        <Line
                                            type="monotone"
                                            dataKey="usage"
                                            stroke="#7c3aed"
                                            strokeWidth={2}
                                            dot={false}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="ramgraph">
                                <span className="label">RAM Usage</span>
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie data={ramData} dataKey="value" nameKey="name" outerRadius={100}>
                                            {ramData.map((_, index) => (<Cell key={index} fill={COLORS[index]} />))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </section>
    );
}