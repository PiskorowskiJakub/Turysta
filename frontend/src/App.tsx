import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout';
import RequireAuth from './components/RequireAuth';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import TutorialPage from './pages/TutorialPage';
import ProfilePage from './pages/ProfilePage';
import TrainingPage from './pages/TrainingPage';
import JobsPage from './pages/JobsPage';
import BadgesPage from './pages/BadgesPage';
import NotFoundPage from './pages/NotFoundPage';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<HomePage />} />
        <Route path="logowanie" element={<LoginPage />} />
        <Route path="rejestracja" element={<RegisterPage />} />
        <Route element={<RequireAuth />}>
          <Route path="samouczek" element={<TutorialPage />} />
          <Route path="profil" element={<ProfilePage />} />
          <Route path="umiejetnosci" element={<Navigate to="/umiejetnosci/podstawowe" replace />} />
          <Route path="umiejetnosci/swiat" element={<Navigate to="/umiejetnosci/awans" replace />} />
          <Route path="umiejetnosci/*" element={<TrainingPage />} />
          <Route path="szkolenia" element={<Navigate to="/umiejetnosci" replace />} />
          <Route path="praca" element={<JobsPage />} />
          <Route path="odznaki" element={<BadgesPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  );
}
