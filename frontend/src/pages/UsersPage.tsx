import {
    UserRoundPlus,
    UserPen,
} from "lucide-react";
import { getUsers } from "../api/user";
import { useEffect, useState } from "react";

export default function Users_page() {
    const [users, setUsers] = useState<any[]>([]);

    useEffect(() => {
        document.title = "Dashboard | Users";

        async function loadUsers() {
            try {
                const { data } = await getUsers();
                setUsers(data);
            } catch (error) {
                console.error(error);
            }
        }

        loadUsers();
    }, []);

    return (
        <section id="users" className="page">
            <div className="table_users">
                <div className="active">
                    <button className="add_users btn_icon">
                        <UserRoundPlus size={20} />
                        Add User
                    </button>

                    <button className="edit_users btn_icon">
                        <UserPen size={20} />
                        Edit User
                    </button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>

                    <tbody>
                        {users.map((user: any) => (
                            <tr key={user.id}>
                                <td>
                                    <input type="checkbox" />
                                </td>
                                <td>{user.name}</td>
                                <td>{user.email}</td>
                                <td>{user.type}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}