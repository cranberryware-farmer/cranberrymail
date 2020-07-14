const camelCase = str => {
  return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(word, index) {
    return word.toUpperCase();
  });
}

export const formatPath = str => {
  let pathArr = str.split(/[./]+/);
  str = pathArr.length > 1 ? pathArr.slice(-1)[0] : pathArr[0];
  str = camelCase(str);
  return str;
}