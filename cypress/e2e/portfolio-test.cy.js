describe('Testing the website', () => {

  const url = 'http://localhost/Test/Portfolio/index.php';

  it('passes', () => {
    cy.visit(url);
    register();
    login();
    
  });

  function register() {
    cy.wait(1500)
    cy.get('.question-button__container > a').click()
    cy.wait(1500)
    cy.get('.register-link').click()
    cy.wait(1500)
    cy.get('[placeholder="Name"]').type('Giovanni');
    cy.wait(500)
    cy.get('[placeholder="Last Name"]').type('Todorevic');
    cy.wait(500)
    cy.get('[placeholder="Job Title"]').type('Web Developer');
    cy.wait(500)
    cy.get('[type="email"]').type('2048757@talnet.nl');
    cy.wait(500)
    cy.get('[type="password"]').type('Test1234');
    cy.wait(500)
    cy.get('[type="submit"]').click();
  } 
  function login() {
    cy.wait(500);
    cy.get('[type="email"]').type('2048757@talnet.nl');
    cy.wait(500);
    cy.get('[type="password"]').type('Test1234');
    cy.wait(500);
    cy.get('[type="submit"]').click();
  }

  function PostQuestion() {
    
  }

})

