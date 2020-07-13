import logo200Image from 'assets/img/logo/cm-logo-web.png';
import logo46Image from 'assets/img/logo/cm-logo-web-icon.png';
import SourceLink from 'components/SourceLink';
import React from 'react';
import axios from 'axios';
import {  withRouter } from 'react-router-dom';
import {
  MdLabel,
} from 'react-icons/md';
import {
  FaSync,
} from 'react-icons/fa';
import { NavLink } from 'react-router-dom';
import {
  Button,
  Nav,
  Navbar,
  NavItem,
  NavLink as BSNavLink,
} from 'reactstrap';
import bn from 'utils/bemnames';
import Cookies from 'js-cookie';
import {
  toast
} from 'react-toastify';
import { folderMaps } from 'constants/folderMapping';
import { formatPath } from 'helpers/folder-render';

const bem = bn.create('sidebar');

class Sidebar extends React.Component {
  state = {
    isOpenComponents: true,
    isOpenContents: true,
    isOpenPages: true,
    navItems: [],
  };

  componentDidMount(){
    if(this.props.location.state!==undefined){
      this.loadFolders();
    }
  }

  loadFolders = () => {
    let app = this.props.location.state;
    const config = {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + app.token,
      }
    };

    axios.post(window._api+"/folders",{},config).then( res => {
      if(res.status===200){
        const result = res.data;
        if(result.hasOwnProperty("success") && result.hasOwnProperty("force_logout") && result.success === false && res.data.force_logout === true){
          Cookies.remove('app_auth');
          Cookies.remove('app_email');
          this.props.history.push('/login');
        } else {
          toast('Welcome to Cranberry Mail');
          let items = [];
          let folders =[];
          for(let i=0;i<result.length;i++){
            let path = result[i];
            let lowerPath = path;
            if(path){
              path = formatPath(path);
              lowerPath = path.toString().toLowerCase();
            }
            items[i]={
              to: result[i],
              name: path,
              exact: true,
              Icon: folderMaps.hasOwnProperty(lowerPath) ? folderMaps[lowerPath] : MdLabel
            };
          }
          for(let i=0;i<items.length; i++){
            folders.push(items[i].name);
          }
          this.props.saveFolders(folders);
          this.props.saveCurFolder(items[0].name);
          this.setState({
            navItems: items
          });
        }
      }
    })
    .catch(error => {
      console.log("Invalid Auth", error);
      Cookies.remove('app_auth');
      Cookies.remove('app_email');
      this.props.history.push('/login');
    });

  };

  handleClick = name => () => {
    this.setState(prevState => {
      const isOpen = prevState[`isOpen${name}`];
      return {
        [`isOpen${name}`]: !isOpen,
      };
    });
  };


  render() {
    return (
      <aside className={bem.b()}>
        <div className={bem.e('content')}>
          <Navbar>
            <SourceLink className="navbar-brand d-flex">
            <img
              src={logo200Image}
              width="192"
              height="40"
              className="cm-logo cm-logo-200"
              alt="CranberryMail"
            />
            <img
              src={logo46Image}
              width="40"
              height="40"
              className="cm-logo cm-logo-46"
              alt="CranberryMail"
            />
            </SourceLink>
          </Navbar>
          <Button
            style={{
              marginLeft: 5,
              marginTop: -5
            }}
            onClick={
              (e) => {
                e.preventDefault();
                this.loadFolders();
              }
            }
          >
            <FaSync />
          </Button>
          <Nav vertical>
            {this.state.navItems.map(({ to, name, exact, Icon }, index) => (
              <NavItem key={index} className={bem.e('nav-item')}>
                <BSNavLink
                  id={`navItem-${name}-${index}`}
                  className=""
                  tag={NavLink}
                  to={to}
                  activeClassName="active"
                  exact={exact}
                  onClick={(e) => {
                    e.preventDefault();
                    this.props.saveCurFolder(to);
                  }}
                >
                  <Icon className={bem.e('nav-item-icon')} />
                  <span className="">{name}</span>
                </BSNavLink>
              </NavItem>
            ))}
          </Nav>
        </div>
      </aside>
    );
  }
}

export default withRouter(Sidebar);
