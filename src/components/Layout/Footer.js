import React from 'react';
import { Navbar, Nav, NavItem } from 'reactstrap';

const Footer = () => {
  let date = new Date();
  return (
    <Navbar>
      <Nav navbar>
        <NavItem>
          &copy; {date.getFullYear()} CranberryMail
        </NavItem>
      </Nav>
    </Navbar>
  );
};

export default Footer;
