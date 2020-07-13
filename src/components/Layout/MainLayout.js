import { Content, Footer, Header, Sidebar } from 'components/Layout';
import React from 'react';
import { withRouter } from 'react-router-dom';
import { Spinner } from 'reactstrap';

class MainLayout extends React.Component {
  state = {
    isCentralLoading: false,
  };

  static isSidebarOpen() {
    return document
      .querySelector('.cr-sidebar')
      .classList.contains('cr-sidebar--open');
  }

  componentWillReceiveProps({ breakpoint }) {
    if (breakpoint !== this.props.breakpoint) {
      this.checkBreakpoint(breakpoint);
    }
  }

  componentDidMount() {
    this.checkBreakpoint(this.props.breakpoint);
  }

  // close sidebar when
  handleContentClick = () => {
    // close sidebar if sidebar is open and screen size is less than `md`
    if (
      MainLayout.isSidebarOpen() &&
      (this.props.breakpoint === 'xs' ||
        this.props.breakpoint === 'sm' ||
        this.props.breakpoint === 'md')
    ) {
      this.openSidebar('close');
    }
  };

  checkBreakpoint(breakpoint) {
    switch (breakpoint) {
      case 'xs':
      case 'sm':
      case 'md':
        return this.openSidebar('close');

      case 'lg':
      case 'xl':
      default:
        return this.openSidebar('open');
    }
  }

  openSidebar(openOrClose) {
    if (openOrClose === 'open') {
      return document
        .querySelector('.cr-sidebar')
        .classList.add('cr-sidebar--open');
    }
    document.querySelector('.cr-sidebar').classList.remove('cr-sidebar--open');
  }

  triggerCentralLoading = param => {
    this.setState({
      isCentralLoading: param,
    });
  }

  render() {
    const { children } = this.props;
    return (
      <main className="cr-app bg-light">
        {this.state.isCentralLoading === true && <div className='cm-page-loader cr-page-spinner'>
                                                    <Spinner
                                                      color = "secondary"
                                                      className='cm-main-loader'
                                                    />
                                                  </div>}
        <Sidebar
          saveCurFolder={this.props.saveCurFolder}
          saveFolders={this.props.saveFolders}
        />
        <Content fluid onClick={this.handleContentClick}>
          <Header
            search={this.props.handleSearch}
            triggerCentralLoading={this.triggerCentralLoading}
          />
          {children}
          <Footer />
        </Content>
      </main>
    );
  }
}

export default withRouter(MainLayout);
