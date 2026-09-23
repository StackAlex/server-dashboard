import axios from "axios";
import { api } from "./api";

export const allContainers = () => {
    return api.get(`/docker/containers`);
};