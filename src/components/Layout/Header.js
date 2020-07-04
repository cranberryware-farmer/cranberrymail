import CAvatar from 'components/CAvatar';
import { UserCard } from 'components/Card';
import Notifications from 'components/Notifications';
import SearchInput from 'components/SearchInput';
import { notificationsData } from 'demos/header';
import withBadge from 'hocs/withBadge';
import React from 'react';
import {  withRouter } from 'react-router-dom';
import {
  MdClearAll,
  MdExitToApp,
  MdHelp,
  MdInsertChart,
  MdMessage,
  MdNotificationsActive,
  MdNotificationsNone,
  MdPersonPin,
  MdSettingsApplications,
} from 'react-icons/md';
import {
  Button,
  ListGroup,
  ListGroupItem,
  // NavbarToggler,
  Nav,
  Navbar,
  NavItem,
  NavLink,
  Popover,
  PopoverBody
} from 'reactstrap';
import bn from 'utils/bemnames';
import axios from 'axios';
import Cookies from 'js-cookie';

const bem = bn.create('header');

const MdNotificationsActiveWithBadge = withBadge({
  size: 'md',
  color: 'primary',
  style: {
    top: -10,
    right: -10,
    display: 'inline-flex',
    justifyContent: 'center',
    alignItems: 'center',
  },
  children: <small>5</small>,
})(MdNotificationsActive);

class Header extends React.Component {
  state = {
    isOpenNotificationPopover: false,
    isNotificationConfirmed: false,
    isOpenUserCardPopover: false,
    name: '',
    email: ''
  };

  toggleNotificationPopover = () => {
    this.setState({
      isOpenNotificationPopover: !this.state.isOpenNotificationPopover,
    });

    if (!this.state.isNotificationConfirmed) {
      this.setState({ isNotificationConfirmed: true });
    }
  };

  toggleUserCardPopover = () => {
    this.setState({
      isOpenUserCardPopover: !this.state.isOpenUserCardPopover,
    });
  };

  handleSidebarControlButton = event => {
    event.preventDefault();
    event.stopPropagation();

    document.querySelector('.cr-sidebar').classList.toggle('cr-sidebar--open');
  };

  componentDidMount(){
    if(this.props.location.state!==undefined){
      this.setState({
        name: this.props.location.state.name,
        email: this.props.location.state.email  
      });
    }
    if(!this.state.email){
      const app_email = Cookies.get("app_email") ? Cookies.get("app_email") : "";
      if(app_email) {
        this.setState({
          email: app_email  
        });
      }
    }
  }

  render() {
    const { isNotificationConfirmed, email } = this.state;
    let EmailName = ' ';
    if(email){
      const NEmailExtract = email.split('@');
      EmailName =  NEmailExtract[0].toString().replace(/[._]/g," ");
    }
    
    return (
      <Navbar light expand className={bem.b('bg-white')}>
        <Nav navbar className="mr-2">
          <Button outline onClick={this.handleSidebarControlButton}>
            <MdClearAll size={25} />
          </Button>
        </Nav>
        <Nav navbar>
          <SearchInput search = {this.props.search}/>
        </Nav>

        <Nav navbar className={bem.e('nav-right')}>
          {/* <NavItem className="d-inline-flex">
            <NavLink id="Popover1" className="position-relative">
              {isNotificationConfirmed ? (
                <MdNotificationsNone
                  size={25}
                  className="text-secondary can-click"
                  onClick={this.toggleNotificationPopover}
                />
              ) : (
                <MdNotificationsActiveWithBadge
                  size={25}
                  className="text-secondary can-click animated swing infinite"
                  onClick={this.toggleNotificationPopover}
                />
              )}
            </NavLink>
            <Popover
              placement="bottom"
              isOpen={this.state.isOpenNotificationPopover}
              toggle={this.toggleNotificationPopover}
              target="Popover1"
            >
              <PopoverBody>
                <Notifications notificationsData={notificationsData} />
              </PopoverBody>
            </Popover>
          </NavItem> */}

          <NavItem>
            <NavLink id="Popover2">
              <CAvatar
                name={EmailName}
                onClick={this.toggleUserCardPopover}
                className="can-click"
              />
            </NavLink>
            <Popover
              placement="bottom-end"
              isOpen={this.state.isOpenUserCardPopover}
              toggle={this.toggleUserCardPopover}
              target="Popover2"
              className="p-0 border-0"
              style={{ minWidth: 250 }}
            >
              <PopoverBody className="p-0 border-light">
                <UserCard
                  name={EmailName}
                  title={this.state.name}
                  subtitle={this.state.email}
                  text="Last updated 3 mins ago"
                  className="border-light"
                >
                  <ListGroup flush>
                    {/* <ListGroupItem tag="a" href="#" className="border-light">
                      <MdPersonPin /> Profile
                    </ListGroupItem>
                    <ListGroupItem tag="a" href="#" className="border-light">
                      <MdInsertChart /> Stats
                    </ListGroupItem>
                    <ListGroupItem tag="a" href="#" className="border-light">
                      <MdMessage /> Messages
                    </ListGroupItem>
                    <ListGroupItem tag="a" href="#" className="border-light">
                      <MdSettingsApplications /> Settings
                    </ListGroupItem>
                    <ListGroupItem tag="a" href="#" className="border-light">
                      <MdHelp /> Help
                    </ListGroupItem> */}
                    <ListGroupItem tag="a" href="#" action className="border-light">
                      <span onClick={()=>{
                        this.props.triggerCentralLoading(true);
                        let el=this;
                        let token =  this.props.location.state.token;
                        const config = {
                          headers: {
                            Accept: 'application/json',
                            Authorization: 'Bearer ' + token,
                          },
                        };
                        axios.post(window._api + '/logout', {}, config)
                        .then(res => {
                          Cookies.remove('app_auth');
                          Cookies.remove('app_email');
                          this.props.triggerCentralLoading(false);
                          el.props.history.push('/login');  
                        })
                        .catch(error => {
                          console.log("Logout Unsuccessful", error);
                        });
                        
                      }}><MdExitToApp /> Signout</span>
                    </ListGroupItem>
                  </ListGroup>
                </UserCard>
              </PopoverBody>
            </Popover>
          </NavItem>
        </Nav>
      </Navbar>
    );
  }
}

export default withRouter(Header);
