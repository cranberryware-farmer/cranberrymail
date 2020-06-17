import { STATE_LOGIN } from 'components/AuthForm';
import GAListener from 'components/GAListener';
import { EmptyLayout, LayoutRoute, MainLayout } from 'components/Layout';
import PageSpinner from 'components/PageSpinner';
import AuthPage from 'pages/AuthPage';
import React from 'react';
import componentQueries from 'react-component-queries';
import { BrowserRouter, Redirect, Route, Switch } from 'react-router-dom';
import './styles/reduction.scss';

const InboxPage = React.lazy(() => import('pages/InboxPage'));

const getBasename = path => path.substr(0, path.lastIndexOf('/'));

class App extends React.Component {
  state = {
    user: {
      name: '',
      email: '',
      password: '',
      token: ''
    },
    
    mail:{
      curFolder: '',
      folders: {},
      imap: {
        host: '',
        port: '',
        encryption:''
      },
      smtp: {
        host: '',
        port: '',
        encryption: ''
      },
      searchTerm: ''
    }
  };

  setUser = (user) => {
    this.setState({ user });
  };

  setImap = (imap) => {
    this.setState({
      mail: {
        imap: imap
      }
    });
  };

  setSmtp = (smtp) => {
    this.setState({
      mail: {
        smtp: smtp
      }
    });
  };

  setCurFolder = (curFolder) => {
    this.setState({
      mail: {
        curFolder: curFolder
      }
    })
  };

  setFolders = (folders) => {
    this.setState({
      mail: {
        folders: folders
      }
    })
  };

  handleSearch = (term) => {
    this.setState({
      searchTerm: term
    });
  };

  render() {
    return (
      <BrowserRouter basename={getBasename(window.location.pathname)}> 
        <GAListener>
          <Switch>
            <LayoutRoute
              exact
              path="/login"
              layout={EmptyLayout}
              component={props => (
                <AuthPage {...props} authState={STATE_LOGIN} saveUser={this.setUser} />
              )}
            />
            
            <MainLayout 
              breakpoint={this.props.breakpoint} 
              saveCurFolder={this.setCurFolder} 
              saveFolders={this.setFolders} 
              mail={this.state.mail} 
              handleSearch={this.handleSearch} >

               <React.Suspense fallback={<PageSpinner />}>
                 <Route exact 
                  path="/" 
                  render = {(props) => <InboxPage {...props} 
                                            curFolder={this.state.mail.curFolder} 
                                            searchTerm={this.state.searchTerm}
                                            breakpoint={this.props.breakpoint}
                                            />} 
                  />
              </React.Suspense>
            </MainLayout>
            <Redirect to="/login" />
          </Switch>
        </GAListener>
      </BrowserRouter>
    );
  }
}

const query = ({ width }) => {
  if (width < 575) {
    return { breakpoint: 'xs' };
  }

  if (576 < width && width < 767) {
    return { breakpoint: 'sm' };
  }

  if (768 < width && width < 991) {
    return { breakpoint: 'md' };
  }

  if (992 < width && width < 1199) {
    return { breakpoint: 'lg' };
  }

  if (width > 1200) {
    return { breakpoint: 'xl' };
  }

  return { breakpoint: 'xs' };
};

export default componentQueries(query)(App);
