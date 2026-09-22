import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/api";
import { useAuth } from "../context/AuthContext";
import axios from "axios";

export default function Login() {
    useEffect(() => {
        document.title = "Dashboard | Login";
    }, []);
    const navigate = useNavigate();
    const { setUser } = useAuth();

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [error,setError]=useState("")

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setError("")

        try {
            const { data } = await api.post("/login", {
                email,
                password,
            });

            localStorage.setItem("token", data.token);

            api.defaults.headers.common.Authorization = `Bearer ${data.token}`;

            setUser(data.user);

            navigate("/");
        } catch (err) {
            console.error(err);

            if (axios.isAxiosError(err)) {
                setError(err.response?.data?.message ?? "An error occurred");
            } else {
                setError("An error occurred");
            }
        }
    }

    return (
        <section className="page" id="login">
            <h1>Dashboard <a href="https://github.com/StackAlex" target="_blank" rel="noopener noreferrer">By StackAlex</a></h1>
            <div className="login_window">
            <h2>Login</h2>
                <form onSubmit={handleSubmit}>
                    {error && <span className="error">{error}</span>}
                    <label htmlFor="email">Email:</label>
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                    />

                    <label htmlFor="password">Password:</label>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                    />

                    <button type="submit">
                        Log In
                    </button>
                </form>
            </div>
        </section>
    );
}