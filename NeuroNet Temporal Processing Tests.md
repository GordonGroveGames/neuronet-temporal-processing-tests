**Game Design Document**  
**NeuroNet Temporal Processing Tests**

---

### **Overview**

The NeuroNet Temporal Processing Tests is a series of six auditory discrimination tests designed for use on iPads or other touch-sensitive input devices. The purpose of the tests is to evaluate a user's ability to match audio stimuli with corresponding visual elements. Users interact with the tests by tapping the correct image that matches the sound they hear. If a user answers three questions incorrectly in a row, the test ends.

The program is designed with configurable elements, allowing administrators to adjust the number of prompts per test.

---

### **Test Structure & Mechanics**

* Each test consists of a background image split into three equal sections: left, center, and right.  
* A sound plays that corresponds to one of the three sections.  
* The user must tap the correct section to match the sound.  
* A correct answer allows the test to proceed.  
* An incorrect answer is logged; three consecutive incorrect answers end the test.

#### **Configurable Elements**

* The number of prompts required to complete each test can be set individually per test.

---

### **Tests**

#### **Test 1: Cat, Dog, Cow (Tutorial)**

* Objective: Teach users the game mechanic.  
* Images: Cat (left), Dog (center), Cow (right).  
* Sounds: "Meow," "Bark," "Moo."

#### **Test 2: Vowel Discrimination**

* Objective: Measure auditory discrimination of vowel sounds.  
* Images: Three human faces making exaggerated vowel sounds.  
* Sounds: "Ahhh" (left), "Ooooo" (center), "Eeee" (right).

#### **Test 3: Consonant Discrimination**

* Objective: Measure auditory discrimination of consonant sounds.  
* Images: Close-up faces forming exaggerated consonant sounds.  
* Sounds: "Ffff" (left), "Shhhh" (center), "Mmmmm" (right).

#### **Test 4: Intonation Patterns**

* Objective: Measure the ability to distinguish intonation in speech.  
* Images:  
  * Left: A surprised person (arms up, palms up, raised eyebrows).  
  * Center: A neutral expression (arms down, palms forward, open mouth, lowered eyes).  
  * Right: A frowning person (arms crossed, stern expression).  
* Sounds: "What?" (left), "Oh" (center), "Uh-oh" (right).

#### **Test 5: Intonation Patterns (Nonsense Sounds)**

* Objective: Measure recognition of intonation patterns without lexical content.  
* Images: Same as Test 4\.  
* Sounds: Nonsense syllables using the "M" consonant (e.g., "Mmm?", "Mmm\!", "Mmm...").

#### **Test 6: Spatial Audio Localization**

* Objective: Measure the ability to distinguish directional sound sources.  
* Images: Three unique cows (left, center, right).  
* Sounds: A cow "moo" sound played from either the left, center, or right audio channel.

---

### **User Flow**

#### **Screen 1: Login & Branding**

* Title: "The NeuroNet Cows Test."  
* User login form (Username \+ Password) and "Login with Google" option.  
* If the user is already logged in, display a "Continue" button instead.

#### **Screen 2: Progress & Go Screen**

* Large "Go" button at the center.  
* Six lily pads at the bottom, with a frog on the first pad.  
* Clicking "Go" begins Test 1\.

#### **Test & Progression Flow**

1. **Test 1** → Return to Progress Screen (Frog jumps to 2nd lily pad, "Go" button appears).  
2. **Test 2** → Return to Progress Screen.  
3. **Test 3** → Return to Progress Screen.  
4. **Test 4** → Return to Progress Screen.  
5. **Test 5** → Return to Progress Screen.  
6. **Test 6** → Return to Progress Screen.  
7. Upon completion of all six tests, the frog reaches the final lily pad, triggering a celebration animation.  
8. After a 5-second delay or three user taps, return to the login screen.

---

### **Data Logging & Reporting**

Each response generates a database entry with the following:

* Unique User Identifier  
* Unique Exam Identifier  
* Test Name  
* Question Number  
* Audio Channel (Left, Center, Right)  
* Response Time (Elapsed time before user input)  
* Selected Answer (Left, Center, Right)

Data should be stored in a structured format (JSON or database) for reporting purposes.

---

### **Technical Requirements**

* Platform: iPad or other touch-sensitive devices.  
* Audio: Stereo sound playback to differentiate left, center, and right channels.  
* UI/UX: Simple, engaging visuals with smooth animations.  
* Backend: Secure user authentication and session management.  
* Data Storage: Local database or cloud storage for response logging.  
* Configurability: Ability to modify the number of prompts per test.

---

### **Contractor Requirements**

* Experience in mobile application development (iOS preferred).  
* Familiarity with game development frameworks (Unity, Unreal, or native iOS development).  
* Expertise in audio processing and spatial sound playback.  
* Experience in database management and user authentication systems.  
* Proven ability to deliver engaging UI/UX design.

---

**Deliverables**

* Functional prototype demonstrating all six tests.  
* Final build ready for deployment.  
* Source code with documentation.  
* Data logging and reporting integration.  
* Configurable settings interface.

**Timeline**

* Milestone 1: UI/UX Prototype (2-3 weeks).  
* Milestone 2: Core Test Functionality Implementation (4-6 weeks).  
* Milestone 3: Audio Processing & Data Logging Integration (6-8 weeks).  
* Milestone 4: Testing & Refinements (8-10 weeks).  
* Milestone 5: Final Delivery & Deployment (10-12 weeks).

---

**Budget** To be determined based on proposals from contractors.

**Contact Information** \[Your Contact Details Here\]

