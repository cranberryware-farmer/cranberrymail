import React from 'react';
import PropTypes from 'utils/propTypes';
import classNames from 'classnames';
import Avatar from 'react-avatar';

const CAvatar = ({
  rounded,
  circle,
  src,
  size,
  tag: Tag,
  className,
  style,
  name,
  ...restProps
}) => {
  const classes = classNames({ 'rounded-circle': circle, rounded }, className);
  if(src) {
    return (
      <Tag
        src={src}
        style={{ width: size, height: size, ...style }}
        className={classes}
        {...restProps}
      />
    );
  } else {
    return (
      <Avatar
        name={name}
        size={size}
        style={{ width: size, height: size, ...style }}
        className={classes}
      />
    );
  }
};

CAvatar.propTypes = {
  tag: PropTypes.component,
  rounded: PropTypes.bool,
  circle: PropTypes.bool,
  size: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  src: PropTypes.string,
  style: PropTypes.object,
};

CAvatar.defaultProps = {
  tag: 'img',
  rounded: false,
  circle: true,
  size: 40,
  src: '',
  style: {},
};

export default CAvatar;
