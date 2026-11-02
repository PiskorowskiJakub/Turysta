import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import VerifyEmailPage from './pages/VerifyEmailPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import TutorialPage from './pages/TutorialPage';
import ProfileShell from './components/ProfileShell';
import EditProfilePage from './pages/EditProfilePage';
import TrainingPage from './pages/TrainingPage';
import JobsPage from './pages/JobsPage';
import BadgesPage from './pages/BadgesPage';
import RankingPage from './pages/RankingPage';
import PlayerPage from './pages/PlayerPage';
import HomeShell from './components/HomeShell';
import AdminPage from './pages/AdminPage';
import { PrivacyPage, TermsPage } from './pages/LegalPages';
import BugReportPage from './pages/BugReportPage';
import NotFoundPage from './pages/NotFoundPage';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<HomePage />} />
        <Route path="logowanie" element={<LoginPage />} />
        <Route path="rejestracja" element={<RegisterPage />} />
        <Route path="potwierdz-email" element={<VerifyEmailPage />} />
        <Route path="zapomniane-haslo" element={<ForgotPasswordPage />} />
        <Route path="nowe-haslo" element={<ResetPasswordPage />} />
        <Route path="regulamin" element={<TermsPage />} />
        <Route path="polityka-prywatnosci" element={<PrivacyPage />} />
        <Route element={<RequireAuth />}>
          <Route path="start/*" element={<HomeShell />} />
          <Route path="zglos-blad" element={<BugReportPage />} />
          <Route path="samouczek" element={<TutorialPage />} />
          <Route path="profil/*" element={<ProfileShell />} />
          <Route path="profil/edycja" element={<EditProfilePage />} />
          <Route path="umiejetnosci" element={<Navigate to="/umiejetnosci/podstawowe" replace />} />
          <Route path="umiejetnosci/swiat" element={<Navigate to="/umiejetnosci/awans" replace />} />
          <Route path="umiejetnosci/*" element={<TrainingPage />} />
          <Route path="szkolenia" element={<Navigate to="/umiejetnosci" replace />} />
          <Route path="praca" element={<JobsPage />} />
          <Route path="odznaki" element={<BadgesPage />} />
          <Route path="ranking" element={<RankingPage />} />
          <Route path="admin/*" element={<AdminPage />} />
          <Route path="gracz/:id" element={<PlayerPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  );
}
