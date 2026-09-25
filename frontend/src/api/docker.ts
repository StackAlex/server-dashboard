import axios from "axios";
import { api } from "./api";

export const allContainers = (agentId: string) => {
    return api.get(`/docker/containers`, {
        params: {
            agent_id: agentId,
        },
    });
};