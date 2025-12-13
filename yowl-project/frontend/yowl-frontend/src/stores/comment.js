import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchToken } from '@/services/apiService';
//import { useToast } from 'vue-toastification';
import api from '@/services/apiService';

//const toast = useToast();
export const useCommentStore = defineStore(
  'comments',
  () => {
    const comments = ref([]);
        

    async function getComments() {
      try {
        const response = await api.get('/comments');
        
        comments.value = response.data.data;
      } catch (e) {
        console.log(e.message);
      }
    }

    async function addComment(comment) {
      let commentToAdd = JSON.stringify(comment);
      try {
        const response = await api.post('/comments', commentToAdd);
        comments.value.unshift(response.data.data);
      } catch (e) {
        //toast.error('Cannot post your comment. Check your internet connexion...');
        console.log(e.message);
      }
    }

    async function updateComment(comment, id) {
      let commentToUpdate = JSON.stringify(comment);
      try {        
        const response = await api.patch(`/comments/${id}`, commentToUpdate);
        await getComments()
        

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
      } catch (e) {
        //toast.error('Cannot update comment. Check your internet connexion...');
        console.log(e.message);
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
      } catch (e) {
        //toast.error('Cannot delete comment. Check your internet connexion...');
        console.log(e.message);
      }
    }

    async function reactToComment(commentId, reaction) {
      try {
        await fetchToken();
        const response = await api.post(`/comments/${commentId}/react`, { reaction });
        console.log(response)
        const index = comments.value.findIndex((comment) => comment.id === commentId);
        if (index !== -1) {
          comments.value[index].nb_like = response.data.nb_like;
          comments.value[index].nb_dislike = response.data.nb_dislike;
          comments.value[index].user_reaction = response.data.user_reaction;
        }

        return response;
      } catch (error) {
        console.error(' Erreur lors de la réaction :', error);
      }
    }

    return {
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
