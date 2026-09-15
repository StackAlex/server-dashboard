import { createBrowserRouter } from "react-router-dom";

import MainLayout from "../layouts/MainLayout";
import Dashboard from "../pages/Dashboard";
import Servers from "../pages/Servers";
import Login from "../pages/Login";
import NotFound from "../pages/NotFound";
import Terminal_page from "../pages/Terminal";
import Settings from "../pages/Settings";
import Log from "../pages/Log";
import Users_page from "../pages/UsersPage"; 
import AgentPage from "../pages/Agents_page"; 
import ProtectedRoute from "./ProtectedRoute";
import Docker_page from "../pages/Docker_page";

export const router = createBrowserRouter([
    {
        element: 
        <ProtectedRoute>
            <MainLayout />
        </ProtectedRoute>,
        children: [
            {
                path: "/",
                element: <Dashboard />,
            },
            {
                path: "/servers",
                element: <Servers />,
            },
            {
                path: "/docker",
                element: <Docker_page />,
            },
            {
                path: "/terminal",
                element: <Terminal_page />,
            },
            {
                path: "/log",
                element: <Log />,
            },
            {
                path: "/users",
                element: <Users_page />,
            },
            {
                path: "/settings",
                element: <Settings />,
            },
            {
                path: "/agents",
                element: <AgentPage />,
            },
        ],
    },
    {
        path: "/login",
        element: <Login />,
    },
    {
        path: "*",
        element: <NotFound />,
    },
]);