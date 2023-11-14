cy.get(".question-button__container > a");
cy.wait(1500);
cy.get("#subject").type("Test");
cy.get("#tag").type("Test");
cy.get("#description").type("Test");
cy.get('[type="submit"]').click();