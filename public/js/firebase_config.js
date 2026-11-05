/**
 * Firebase Web Client SDK Configuration & Cloud Services Hub.
 *
 * File: js/firebase_config.js
 * Project: amulet-c509e
 * Mutation Log: 2026-11-05 - Configured Firebase App, Analytics, Firestore, and Realtime Database integrations.
 */

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-analytics.js";
import { getFirestore, collection, getDocs, doc, setDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { getDatabase, ref, set, onValue } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js";

// Your web app's Firebase configuration
export const firebaseConfig = {
  apiKey: "AIzaSyBV0rQEnlmGh8XOP2g0_akE5Jif-3iDDT0",
  authDomain: "amulet-c509e.firebaseapp.com",
  projectId: "amulet-c509e",
  storageBucket: "amulet-c509e.firebasestorage.app",
  messagingSenderId: "972034311886",
  appId: "1:972034311886:web:27e186d091b0a3619ff29f",
  measurementId: "G-L3C8EDSEC7"
};

// Initialize Firebase
export const app = initializeApp(firebaseConfig);
export const analytics = typeof window !== 'undefined' ? getAnalytics(app) : null;
export const db = getFirestore(app);
export const rtdb = getDatabase(app);

console.info('[FIREBASE]', 'Firebase connected successfully for project:', firebaseConfig.projectId);
