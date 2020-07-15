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
  Spinner,
  ListGroup,
  ListGroupItem
} from 'reactstrap';
import {
  FaEye,
  FaEyeSlash
} from 'react-icons/fa';
import { withRouter } from 'react-router-dom';
import Cookies from 'js-cookie';
import { validateEmail } from 'helpers/common-functions';

class AuthForm extends React.Component {
  constructor(props){
    super(props);
    this.state = {
      name: '',
      email: '',
      password: '',
      ptype: 'password',
      cbox: '',
      token: '',
      isValidated: 0, // 0=loaded, 1=pass, 2=fail
      validationMessage: []
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

  togglePassword = () => {
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
    
    let error = false;

    let errorMessages = [];

    if ( !this.state.email ) {
      errorMessages.push(<ListGroupItem color="danger" className="cm-validation-message">Please fill Email</ListGroupItem>);
      error = true;
    } else {
      if (!validateEmail(this.state.email)) {
        errorMessages.push(<ListGroupItem color="danger" className="cm-validation-message">Email is not valid</ListGroupItem>);
        error = true;
      }
    }
    if ( !this.state.password ) {
      errorMessages.push(<ListGroupItem color="danger" className="cm-validation-message">Please fill Password</ListGroupItem>);
      error = true;
    }

    if (!error && this.isLogin) {
      this.setState({
        isLoading : true,
        validationMessage: []
      });
      const fields = {
        email: this.state.email,
        password: this.state.password,
        cbox: this.state.cbox
      };

      axios.interceptors.response.use(response => {
        return response;
      }, error => {
        if (error.response.status === 401) {
          const errMsg = [<ListGroupItem color="danger" className="cm-validation-message">Incorrect email or password</ListGroupItem>];
          this.setState({
            isLoading : false,
            validationMessage: errMsg
          });
        }
        return Promise.reject(error);
      });

      axios.post(window._api+"/login",fields).then(res => {
        if(res.data.status===1){
          this.setState({
            token: res.data.success.token,
            isLoading : false,
            validationMessage: []
          });

          this.props.history.push({
            pathname: '/',
            state: {
              detail: "Logged into your mailbox.",
              email: this.state.email,
              token: res.data.success.token
            }
          });
          Cookies.set('app_auth', res.data.success.token);
          Cookies.set('app_email', this.state.email);
        } else {
          let errMsgs = [];
          if(res.data.hasOwnProperty('error') && Object.keys(res.data.error).length > 0) {
            const errors = res.data.error;
            if(errors.hasOwnProperty('email') && errors.email.length > 0) {
              errors.email.forEach(item => {
                errMsgs.push(<ListGroupItem color="danger" className="cm-validation-message">{item}</ListGroupItem>);
              });
            }
            if(errors.hasOwnProperty('password') && errors.password.length > 0) {
              errors.password.forEach(item => {
                errMsgs.push(<ListGroupItem color="danger" className="cm-validation-message">{item}</ListGroupItem>);
              });
            }
          }
          this.setState({
            isLoading : false,
            validationMessage: errMsgs
          });
        }
      })
      .catch(error => {
        console.log("Invalid Creds", error);
      });
    } else {
      this.setState({
        isValidated : 2,
        validationMessage: errorMessages
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
      <Form onSubmit={!this.state.isLoading ? this.handleSubmit : null}>
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
          <Input 
            {...usernameInputProps}
            onChange={e => this.setState({ email: e.target.value })}
            required
          />
        </FormGroup>
        <FormGroup>
          <Label for={passwordLabel}>{passwordLabel}</Label>
          <InputGroup>
            <Input
              {...passwordInputProps}
              onChange={e => this.setState({ password: e.target.value })}
              type={this.state.ptype}
              required
            />
            <InputGroupAddon addonType="append">
              <Button onClick={ () => this.togglePassword() }>
                {this.state.ptype==="password" ? <FaEye />:<FaEyeSlash />}
              </Button>
            </InputGroupAddon>
          </InputGroup>
        </FormGroup>

        <hr />
        <Button
          size="lg"
          className="bg-gradient-theme-left border-0"
          block
          disabled={this.state.isLoading}
        >
          {this.renderButtonText()}
          {this.state.isLoading ? <Spinner type = "grow" color = "light"/> : null}
        </Button>
        {children}
        <ListGroup className="mt-2">
          {this.state.validationMessage}
        </ListGroup>
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
