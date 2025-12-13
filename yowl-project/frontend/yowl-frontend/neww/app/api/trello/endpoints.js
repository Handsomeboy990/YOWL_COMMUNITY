export const trelloEndpoints = {
    workspaces: {
      getMine: "/members/me/organizations",
      create: "/organizations",
      update: (id) => `/organizations/${id}`,
      delete: (id) => `/organizations/${id}`,
    },
  };
  