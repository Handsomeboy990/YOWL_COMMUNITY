// import React, { useState } from 'react';
// import {
//   View,
//   Text,
//   TextInput,
//   TouchableOpacity,
//   StyleSheet,
//   Alert,
//   ActivityIndicator,
//   ScrollView,
// } from 'react-native';
// import { useAuthStore } from '../../../store/authStore';
// import { TRELLO_AUTH_URL } from '../../../api/trello/constants';
// import * as WebBrowser from 'expo-web-browser';

// export default function LoginScreen() {
//   const [loading, setLoading] = useState(false);
//   const [manualToken, setManualToken] = useState("");
//   const setAuth = useAuthStore((state) => state.setAuth);

//   const handleTrelloLogin = async () => {
//     setLoading(true);
//     try {
//       const result = await WebBrowser.openAuthSessionAsync(
//         `${TRELLO_AUTH_URL}`,
//       );

//       if (result.type === 'success') {
//         const url = result.url;
//         const tokenMatch = url.match(/token=([^&]+)/);

//         if (tokenMatch && tokgitenMatch[1]) {
//           await setAuth(tokenMatch[1]);
//           Alert.alert('Succès', 'Connexion réussie !');
//         } else {
//           Alert.alert('Erreur', 'Impossible de récupérer le token');
//         }
//       }
//     } catch (error) {
//       Alert.alert('Erreur', 'Échec de la connexion');
//     } finally {
//       setLoading(false);
//     }
//   };

//   const handleManualTokenLogin = async () => {
//     if (!manualToken.trim()) {
//       return Alert.alert("Erreur", "Veuillez coller un token.");
//     }

//     setLoading(true);
//     try {
//       await setAuth(manualToken.trim());
//       Alert.alert("Succès", "Connexion réussie !");
//     } catch (err) {
//       Alert.alert("Erreur", "Token invalide");
//     } finally {
//       setLoading(false);
//     }
//   };

//   return (
//     <ScrollView contentContainerStyle={styles.container}>
//       <View style={styles.header}>
//         <Text style={styles.title}>TrellTech</Text>
//         <Text style={styles.subtitle}>Gestion de Projets</Text>
//       </View>

//       <View style={styles.content}>
//         <Text style={styles.description}>
//           Connectez-vous avec votre compte Trello pour gérer vos projets
//         </Text>

//         <TouchableOpacity
//           style={[styles.button, styles.primaryButton]}
//           onPress={handleTrelloLogin}
//           disabled={loading}
//         >
//           {loading ? (
//             <ActivityIndicator color="#fff" />
//           ) : (
//             <Text style={styles.buttonText}>Se connecter avec Trello</Text>
//           )}
//         </TouchableOpacity>


//         <Text style={styles.manualLabel}>Ou collez votre token manuellement :</Text>

//         <TextInput
//           style={styles.input}
//           placeholder="Collez votre token Trello ici"
//           value={manualToken}
//           onChangeText={setManualToken}
//           autoCapitalize="none"
//         />

//         <TouchableOpacity
//           style={[styles.button, styles.secondaryButton]}
//           onPress={handleManualTokenLogin}
//           disabled={loading}
//         >
//           <Text style={styles.secondaryButtonText}>Utiliser le token manuel</Text>
//         </TouchableOpacity>

//         <View style={styles.infoBox}>
//           <Text style={styles.infoText}>
//             Pour obtenir un token Trello, visitez:{"\n"}
//             https://trello.com/1/authorize?expiration=30days&name=TrellTech&scope=read,write&response_type=token
//           </Text>
//         </View>
//       </View>
//     </ScrollView>
//   );
// }

// const styles = StyleSheet.create({
//   container: {
//     flexGrow: 1,
//     backgroundColor: '#F4F5F7',
//     padding: 20,
//   },
//   header: {
//     alignItems: 'center',
//     marginTop: 60,
//     marginBottom: 40,
//   },
//   title: {
//     fontSize: 32,
//     fontWeight: 'bold',
//     color: '#0079BF',
//     marginBottom: 8,
//   },
//   subtitle: {
//     fontSize: 16,
//     color: '#5E6C84',
//   },
//   content: {
//     flex: 1,
//   },
//   description: {
//     fontSize: 16,
//     color: '#5E6C84',
//     textAlign: 'center',
//     marginBottom: 40,
//   },
//   manualLabel: {
//     marginTop: 20,
//     marginBottom: 8,
//     fontSize: 14,
//     color: '#5E6C84',
//   },
//   input: {
//     backgroundColor: '#fff',
//     borderWidth: 1,
//     borderColor: '#0079BF',
//     padding: 12,
//     borderRadius: 8,
//     fontSize: 14,
//     marginBottom: 12,
//   },
//   button: {
//     padding: 16,
//     borderRadius: 8,
//     alignItems: 'center',
//     marginBottom: 12,
//   },
//   primaryButton: {
//     backgroundColor: '#0079BF',
//   },
//   secondaryButton: {
//     borderWidth: 1,
//     borderColor: '#0079BF',
//     backgroundColor: 'transparent',
//   },
//   buttonText: {
//     color: '#fff',
//     fontSize: 16,
//     fontWeight: '600',
//   },
//   secondaryButtonText: {
//     color: '#0079BF',
//     fontSize: 16,
//     fontWeight: '600',
//   },
//   infoBox: {
//     backgroundColor: '#fff',
//     padding: 16,
//     borderRadius: 8,
//     marginTop: 20,
//     borderLeftWidth: 4,
//     borderLeftColor: '#FFAB4A',
//   },
//   infoText: {
//     fontSize: 12,
//     color: '#5E6C84',
//   },
// });


import React, { useState } from "react";
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
  Platform
} from "react-native";

import * as WebBrowser from "expo-web-browser";
import * as Linking from "expo-linking";

import { useAuthStore } from "../../../store/authStore";
import {
  TRELLO_AUTH_URL,
  getTrelloAuthUrlWithCallback,
} from "../../../api/trello/constants";

export default function LoginScreen() {
  const [loading, setLoading] = useState(false);
  const [manualToken, setManualToken] = useState("");
  const setAuth = useAuthStore((state) => state.setAuth);

  const handleTrelloLogin = async () => {
    setLoading(true);

    try {
      const redirectUri = Linking.createURL("/");
      const authUrl = getTrelloAuthUrlWithCallback(redirectUri);

      if (Platform.OS === "web") {
        // 💻 WEB → redirection directe → compatible Hermes
        window.location.href = authUrl;
        return;
      }

      // 📱 MOBILE → utilise openAuthSessionAsync
      const result = await WebBrowser.openAuthSessionAsync(authUrl, redirectUri);

      if (result.type === "success" && result.url) {
        const tokenMatch = result.url.match(/token=([^&]+)/);

        if (tokenMatch && tokenMatch[1]) {
          await setAuth(tokenMatch[1]);
          Alert.alert("Succès", "Connexion réussie !");
          return;
        }
      }

      Alert.alert("Erreur", "Impossible de récupérer le token via Trello.");
    } catch (error) {
      console.log("Auth error:", error);
      Alert.alert("Erreur", "Échec de la connexion Trello.");
    } finally {
      setLoading(false);
    }
  };

  const handleManualTokenLogin = async () => {
    if (!manualToken.trim()) {
      return Alert.alert("Erreur", "Veuillez coller votre token.");
    }

    setLoading(true);

    try {
      await setAuth(manualToken.trim());
      Alert.alert("Succès", "Connexion réussie !");
    } catch (err) {
      Alert.alert("Erreur", "Token invalide.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>TrellTech</Text>
        <Text style={styles.subtitle}>Gestion de Projets</Text>
      </View>

      <View style={styles.content}>
        <Text style={styles.description}>
          Connectez-vous avec votre compte Trello pour gérer vos projets
        </Text>

        <TouchableOpacity
          style={[styles.button, styles.primaryButton]}
          onPress={handleTrelloLogin}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.buttonText}>Se connecter avec Trello</Text>
          )}
        </TouchableOpacity>

        <Text style={styles.manualLabel}>Ou collez votre token manuellement :</Text>

        <TextInput
          style={styles.input}
          placeholder="Collez votre token Trello ici"
          value={manualToken}
          onChangeText={setManualToken}
          autoCapitalize="none"
        />

        <TouchableOpacity
          style={[styles.button, styles.secondaryButton]}
          onPress={handleManualTokenLogin}
          disabled={loading}
        >
          <Text style={styles.secondaryButtonText}>
            Utiliser le token manuel
          </Text>
        </TouchableOpacity>

        <View style={styles.infoBox}>
          <Text style={styles.infoText}>
            Pour obtenir un token Trello :{"\n"}
            https://trello.com/1/authorize?expiration=30days&name=TrellTech&scope=read,write&response_type=token
          </Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flexGrow: 1,
    backgroundColor: "#F4F5F7",
    padding: 20,
  },
  header: {
    alignItems: "center",
    marginTop: 60,
    marginBottom: 40,
  },
  title: {
    fontSize: 32,
    fontWeight: "bold",
    color: "#0079BF",
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: "#5E6C84",
  },
  content: {
    flex: 1,
  },
  description: {
    fontSize: 16,
    color: "#5E6C84",
    textAlign: "center",
    marginBottom: 40,
  },
  manualLabel: {
    marginTop: 20,
    marginBottom: 8,
    fontSize: 14,
    color: "#5E6C84",
  },
  input: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#0079BF",
    padding: 12,
    borderRadius: 8,
    fontSize: 14,
    marginBottom: 12,
  },
  button: {
    padding: 16,
    borderRadius: 8,
    alignItems: "center",
    marginBottom: 12,
  },
  primaryButton: {
    backgroundColor: "#0079BF",
  },
  secondaryButton: {
    borderWidth: 1,
    borderColor: "#0079BF",
    backgroundColor: "transparent",
  },
  buttonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "600",
  },
  secondaryButtonText: {
    color: "#0079BF",
    fontSize: 16,
    fontWeight: "600",
  },
  infoBox: {
    backgroundColor: "#fff",
    padding: 16,
    borderRadius: 8,
    marginTop: 20,
    borderLeftWidth: 4,
    borderLeftColor: "#FFAB4A",
  },
  infoText: {
    fontSize: 12,
    color: "#5E6C84",
  },
});
