import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { api } from "../api/api";
import { useAuth } from "../context/AuthContext";
import axios from "axios";

export default function Docker_page() {
    

    return (
        <section className="page" id="docker">
            <h1>Docker</h1>
            <div className="docker_window">
                <h2>Docker Page</h2>
                <p>This is the Docker page.</p>
            </div>
        </section>
    );
}