import { BrowserRouter, NavLink, Routes, Route } from "react-router-dom";

import Home from "./PageHome";
import Blogs from "./PageBlogs";
import Contact from "./PageContact";
import NoPage from "./PageNoPage";

import Info from "./Component/Info";
import Profil from "./Component/Profil";

import "./styles/Layout.scss";

const linkClass = ({ isActive }) => (isActive ? "nav-active" : undefined);

const Layout = () => {
  return (
    <>
      <BrowserRouter>
        <nav>
          <NavLink to="/" end className={linkClass}>Home</NavLink>
          <NavLink to="/blogs" className={linkClass}>Blogs</NavLink>
          <NavLink to="/contact" className={linkClass}>Contact</NavLink>
        </nav>

        <div className="Page">
          <header>
            <Info />
            <Profil />
          </header>
          <section>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="blogs" element={<Blogs />} />
              <Route path="contact" element={<Contact />} />
              <Route path="*" element={<NoPage />} />
            </Routes>
          </section>
        </div>
      </BrowserRouter>

      <footer>
        <h3>Footer</h3>
      </footer>
    </>
  );
};

export default Layout;
