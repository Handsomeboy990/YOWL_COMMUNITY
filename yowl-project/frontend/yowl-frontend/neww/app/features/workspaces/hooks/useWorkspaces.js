import { useEffect, useState } from "react";
import { getWorkspaces } from "../services/workspaceService";

export const useWorkspaces = () => {
  const [workspaces, setWorkspaces] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchWorkspaces = async () => {
    try {
      setLoading(true);
      const data = await getWorkspaces();
      setWorkspaces(data);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWorkspaces();
  }, []);

  return {
    workspaces,
    loading,
    error,
    refetch: fetchWorkspaces,
  };
};
