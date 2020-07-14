import AuthForm, { STATE_LOGIN } from 'components/AuthForm';
import React from 'react';
import { Card, Col, Row } from 'reactstrap';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import Cookies from 'js-cookie';

class AuthPage extends React.Component {
  handleAuthState = authState => {
    if (authState === STATE_LOGIN) {
      this.props.history.push('/login');
    } else {
      this.props.history.push('/signup');
    }
  };

  componentDidMount() {
    const token = Cookies.get("app_auth") ? Cookies.get("app_auth") : "";
    const email = Cookies.get("app_email") ? Cookies.get("app_email") : "";
    if(token){
      this.props.history.push({
        pathname: '/',
        state: {
          detail: "Retreived mailbox.",
          token,
          email
        }
      });
    }
  };

  handleLogoClick = () => {
    this.props.history.push('/');
  };

  render() {
    return (
      <React.Fragment>
        <Row>
          <ToastContainer />
        </Row>
        <Row
          style={{
            height: '80vh',
            justifyContent: 'center',
            alignItems: 'center',
          }}>
          <Col md={6} lg={4}>
            <Card body>
              <AuthForm
                authState={this.props.authState}
                onChangeAuthState={this.handleAuthState}
                onLogoClick={this.handleLogoClick}
              />
            </Card>
          </Col>
        </Row>
      </React.Fragment>
    );
  }
}

export default AuthPage;
