import { defineStore } from 'pinia';
import { ref } from 'vue';

//import { useToast } from 'vue-toastification';
import api from '@/services/apiService';

//const toast = useToast();
export const useCommentStore = defineStore(
  'comments',
  () => {
    const comments = ref([]);
        

    /**
     * Pose les commentaires d'un avis comme contenu du magasin.
     *
     * La page d'un avis reçoit déjà ses commentaires, réponses comprises,
     * dans la réponse de l'API. Elle appelait pourtant getComments(), qui
     * rapatrie les dix derniers commentaires du site entier : sur une base
     * qui en compte des milliers, ceux de l'avis ouvert n'y étaient
     * pratiquement jamais. La liste restait vide pendant que le compteur,
     * lui, retombait sur la bonne source et affichait le bon nombre.
     *
     * @param {Array} liste les commentaires de l'avis, tels que l'API les rend
     */
    function setForReview(liste) {
      comments.value = Array.isArray(liste) ? [...liste] : [];
    }

    async function getComments() {
      try {
        const response = await api.get('/comments');
        
        comments.value = response.data.data;
      } catch (err) {
        // Silent error handling
    }
    }

    async function addComment(comment) {
      let commentToAdd = JSON.stringify(comment);
      try {
        const response = await api.post('/comments', commentToAdd);
        comments.value.unshift(response.data.data);
      } catch (err) {
        //toast.error('Cannot post your comment. Check your internet connexion...');
      }
    }

    async function updateComment(comment, id) {
      let commentToUpdate = JSON.stringify(comment);
      try {        
        const response = await api.patch(`/comments/${id}`, commentToUpdate);

        // Anciennement suivi d'un getComments(), qui remplaçait les
        // commentaires de l'avis ouvert par les dix derniers du site : la
        // liste se vidait à la première modification.
        const modifie = response?.data?.data;
        if (modifie) {
          const index = comments.value.findIndex((c) => c.id === modifie.id);
          if (index !== -1) comments.value.splice(index, 1, modifie);
        }
        

        if (!response.ok) {
          if (response.status === 404) {
            //toast.error('Cannot find your comment');
          } else {
            //throw new Error('Cannot update comment');
          }
        } else {
        //   let updatedComment = await response.json();
          //toast.success('Your comment has been updated');
          /* actualComment.value = null;
          for (let i = 0; i < comments.value.length; i++) {
            if (comments.value[i].id === updatedComment.id) {
              comments.value.splice(i, 1, updatedComment);
            }
          } */
        }
      } catch (err) {
        //toast.error('Cannot update comment. Check your internet connexion...');
      }
    }

    async function deleteComment(commentId) {
      try {
        const response = await api.delete(`/comments/${commentId}`);
        //await getComments()
        
        comments.value = comments.value.filter((comment) => comment.id != commentId);
        
        if (!response.ok) {
          if (response.status === 404) {
            // toast.error('Cannot find your comment !');
          } else if (response.status === 410) {
            // toast.error('This comment has already been deleted !');
          } else {
            //throw new Error('An error occured during deletion');
          }
        } else {
          //toast.success('Comment deleted !');
        }
      } catch (err) {
        //toast.error('Cannot delete comment. Check your internet connexion...');
      }
    }

    async function reactToComment(commentId, reaction) {
      try {
        const response = await api.post(`/comments/${commentId}/react`, { reaction });
        const index = comments.value.findIndex((comment) => comment.id === commentId);
        if (index !== -1) {
          comments.value[index].nb_like = response.data.nb_like;
          comments.value[index].nb_dislike = response.data.nb_dislike;
          comments.value[index].user_reaction = response.data.user_reaction;
        }

        return response;
      } catch (err) {
        // Silent error handling
    }
    }

    return {
      setForReview,
      comments,
      getComments,   
      addComment,
      updateComment,
      deleteComment,
      reactToComment
    };
  },
  {
    persist: true,
  }
);
