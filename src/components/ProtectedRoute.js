import React from 'react';
import {Route} from 'react-router-dom';
const ProtectedRoute = (props) => {
  return (
    <Route exact 
      path={path} 
      render = {(props) => <Component {...others} />} 
    />
  );

}

export default ProtectedRoute;