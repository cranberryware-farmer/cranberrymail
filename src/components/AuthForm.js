import logo200Image from 'assets/img/logo/logo_200.png';
import PropTypes from 'prop-types';
import React from 'react';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import axios from 'axios';
import { 
  Button, 
  Form, 
  FormGroup, 
  Input, 
  Label,
  InputGroup,
  InputGroupAddon,
  Spinner } from 'reactstrap';

  import {
    FaEye,
    FaEyeSlash
  } from 'react-icons/fa';

import {  withRouter } from 'react-router-dom';
import Cookies from 'js-cookie';


class AuthForm extends React.Component {
  constructor(props){
    super(props);
    this.state = {
      name: '',
      email: '',
      password: '',
      ptype: 'password',
      cbox: '',
      token: ''
    };
  }
  get isLogin() {
    return this.props.authState === STATE_LOGIN;
  }

  get propState(){
    return this.props;
  }

  changeAuthState = authState => event => {
    event.preventDefault();

    this.props.onChangeAuthState(authState);
  };

  togglePassword = (e) => {
      if(this.state.ptype==="password"){
        this.setState({
          ptype: "text"
        });
      }else{
        this.setState({
          ptype: "password"
        });
      }
  };

  handleSubmit = event => {
    event.preventDefault();
    this.setState({isLoading : true});
    
    if(this.isLogin){
      const fields = {
        email: this.state.email,
        password: this.state.password,
        cbox: this.state.cbox
      };

      axios.interceptors.response.use(response => {
        return response;
      }, error => {
        if (error.response.status === 401) {
          toast("Incorrect email or password");
          this.setState({isLoading : false});
        }
        return Promise.reject(error);
      });

      axios.post(window._api+"/login",fields).then(res => { 
        if(res.data.status===1){
          this.setState({
            token: res.data.success.token
          });
          this.setState({isLoading : false});
          
          this.props.history.push({
            pathname: '/',
            state: {
              detail: "Logged into your mailbox.",
              email: this.state.email,
              token: res.data.success.token
            }
          }); 
          Cookies.set('app_auth', res.data.success.token);
        }
      })
      .catch(error => {
        console.log("Invalid Creds", error);
      });
    }

    
  };

  renderButtonText() {
    const { buttonText } = this.props;

    if (!buttonText && this.isLogin) {
      return 'Login';
    }

    return buttonText;
  }

  render() {
    const {
      showLogo,
      usernameLabel,
      usernameInputProps,
      passwordLabel,
      passwordInputProps,
      children,
      onLogoClick,
    } = this.props;

    return (
      <Form onSubmit={this.handleSubmit}>
        {showLogo && (
          <div className="text-center pb-4">
            <img
              src={logo200Image}
              className="rounded"
              style={{ width: 60, height: 60, cursor: 'pointer' }}
              alt="logo"
              onClick={onLogoClick}
            />
          </div>
          
        )}
        <FormGroup>
          <Label for={usernameLabel}>{usernameLabel}</Label>
          <Input {...usernameInputProps} onChange={e => this.setState({ email: e.target.value })}/>
        </FormGroup>
        <FormGroup>
          <Label for={passwordLabel}>{passwordLabel}</Label>
          <InputGroup>
          <Input {...passwordInputProps} onChange={e => this.setState({ password: e.target.value })} type={this.state.ptype} />
            <InputGroupAddon addonType="append">
        <Button onClick={ e => this.togglePassword(e) }>{this.state.ptype==="password" ? <FaEye />:<FaEyeSlash />}</Button>
            </InputGroupAddon>
            
          </InputGroup>
        </FormGroup>
               
        <hr />
        <Button
          size="lg"
          className="bg-gradient-theme-left border-0"
          block
          onClick={!this.state.isLoading ? this.handleSubmit : null}
          disabled={this.state.isLoading}>
          {this.renderButtonText()}
          {this.state.isLoading ? < Spinner 
            type = "grow"
            color = "light" 
          /> : null}
        </Button>

        

        {children}
      </Form>
    );
  }
}

export const STATE_LOGIN = 'LOGIN';

AuthForm.propTypes = {
  authState: PropTypes.oneOf([STATE_LOGIN]).isRequired,
  showLogo: PropTypes.bool,
  nameLabel: PropTypes.string,
  nameInputProps: PropTypes.object,
  usernameLabel: PropTypes.string,
  usernameInputProps: PropTypes.object,
  passwordLabel: PropTypes.string,
  passwordInputProps: PropTypes.object,
  confirmPasswordLabel: PropTypes.string,
  confirmPasswordInputProps: PropTypes.object,
  onLogoClick: PropTypes.func,
};

AuthForm.defaultProps = {
  authState: 'LOGIN',
  showLogo: true,
  nameLabel: 'Name',
  nameInputProps: {
    type: 'text',
    placeholder: 'Your full name',
  },
  usernameLabel: 'Email',
  usernameInputProps: {
    type: 'email',
    placeholder: 'your@email.com',
  },
  passwordLabel: 'Password',
  passwordInputProps: {
    type: 'password',
    placeholder: 'your password',
  },
  onLogoClick: () => {},
};

export default withRouter(AuthForm);
