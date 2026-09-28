import { api } from "../api/api";

export const getSettings = () => {
    return api.get(`/dashboard/settings`);
};

export const SaveSettings = (type: string, data: Record<string, string[]>) => {
    return api.post("/dashboard/settings/save", {
        type,
        data,
    });
};