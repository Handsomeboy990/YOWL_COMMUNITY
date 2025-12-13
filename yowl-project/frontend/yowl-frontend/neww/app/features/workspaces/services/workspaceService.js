import { trelloClient } from "../../../api/trello/client";
import { trelloEndpoints } from "../../../api/trello/endpoints";

export const getWorkspaces = () => {
  return trelloClient.get(trelloEndpoints.workspaces.getMine);
};

export const createWorkspace = ({ name, desc }) => {
  return trelloClient.post(trelloEndpoints.workspaces.create, {
    displayName: name,
    desc,
  });
};

export const updateWorkspace = (id, { name, desc }) => {
  return trelloClient.put(trelloEndpoints.workspaces.update(id), {
    displayName: name,
    desc,
  });
};

export const deleteWorkspace = (id) => {
  return trelloClient.delete(trelloEndpoints.workspaces.delete(id));
};
