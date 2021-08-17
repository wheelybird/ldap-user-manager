'use strict';

//Adapted from https://github.com/mike-hearn/useapassphrase

function generatePassword(numberOfWords,seperator,passwordField,confirmField) {

  // IDs of password field and confirm field
  var passwordField = document.getElementById(passwordField);
  var confirmField = document.getElementById(confirmField);

  // Cryptographically generated random numbers
  numberOfWords = parseInt(numberOfWords);

  var array = new Uint32Array(numberOfWords);
  var crypto = window.crypto || window.msCrypto;
  crypto.getRandomValues(array);

  // Empty array to be filled with wordlist
  var generatedPasswordArray = [];

  var integerIndex = getRandomInt(4);
  var integerValue = getRandomInt(99);
  
  var uppercaseIndex = getRandomInt(4);
  while (uppercaseIndex == integerIndex) {
   uppercaseIndex = getRandomInt(4);
  }

  // Grab a random word, push it to the password array
  for (var i = 0; i < array.length; i++) {
      
      var this_word = "";
      
      if (i == integerIndex ) {
       
       this_word = integerValue;
       
      }
      else {
      
       var index = (array[i] % 5852);
       this_word = wordlist[index];
      
       if (i == uppercaseIndex) {
        this_word = this_word[0].toUpperCase() + this_word.slice(1);
       }
      
      }
      
      generatedPasswordArray.push(this_word);
  }

  var this_password = generatedPasswordArray.join(seperator);

  passwordField.type = 'text';
  passwordField.value = this_password;

  confirmField.type = 'text';
  confirmField.value = this_password;

  //Copy to the clipboard
  passwordField.focus();
  passwordField.select();
  document.execCommand("copy");

}


function getRandomInt(max) {
  return Math.floor(Math.random() * Math.floor(max));
}
