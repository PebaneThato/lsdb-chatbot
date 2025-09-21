import { Component, OnInit } from '@angular/core';
import { ChatbotService } from '../../services/chatbot.service';

export interface User {
  name: string;
  email: string;
}

export interface ChatMessage {
  type: 'bot' | 'user';
  content: string;
  timestamp: Date;
}

export interface MainOptions {
  data : ChatOption[];
}

export interface ChatOption {
  id: string;
  text: string;
  link?: string;
}

@Component({
  selector: 'app-chatbot',
  templateUrl: './chatbot.component.html',
  styleUrls: ['./chatbot.component.scss']
})
export class ChatbotComponent implements OnInit {
  isOpen = false;
  showUserForm = true;
  currentUser: User = { name: '', email: '' };
  messages: ChatMessage[] = [];
  currentOptions: ChatOption[] = [];
  activeOption: string = '';
  
  constructor(private chatbotService: ChatbotService) {}

  ngOnInit() {
    // Initialize chatbot data
  }

  toggleChatbot() {
    this.isOpen = !this.isOpen;
  }

  openChatbot() {
    this.isOpen = true;
  }

  closeChatbot() {
    this.isOpen = false;
  }

  async startChat(userData: User) {
    if (!userData.name || !userData.email) {
      return;
    }

    if (!this.isValidEmail(userData.email)) {
      return;
    }

    try {
      // Save user data to backend
      await this.chatbotService.saveUser(userData).toPromise();
      
      this.currentUser = userData;
      this.showUserForm = false;
      
      // Start conversation
      this.addBotMessage(`Hello ${userData.name}! 👋<br>Hey there! Please select an option to get started.`);
      this.showMainOptions();
    } catch (error) {
      console.error('Error saving user:', error);
    }
  }

  private isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  private addBotMessage(content: string) {
    this.messages.push({
      type: 'bot',
      content,
      timestamp: new Date()
    });
  }

  private addUserMessage(content: string) {
    this.messages.push({
      type: 'user',
      content,
      timestamp: new Date()
    });
  }

  private async showMainOptions() {
    try {
      const options = await this.chatbotService.getMainOptions().toPromise();
      this.currentOptions = options!.data;
    } catch (error) {
      console.error('Error loading main options:', error);
    }
  }

  async handleMainOption(option: any) {
    this.addUserMessage(option.text);
    this.currentOptions = [];
    try {
      switch(option.id) {
        case 'courses':
          await this.showCourses();
          this.activeOption = 'Course';
          break;
        case 'internships':
          await this.showInternships();
          this.activeOption = 'Internship';
          break;
        case 'contact':
          await this.showContactInfo();
          break;
        default:
          await this.showDetails(option);
          break;
      }
    } catch (error) {
      console.error('Error handling option:', error);
    }
  }

  private async showCourses() {
    this.addBotMessage('We offer the following digital courses, Please select one:');
    const courses = await this.chatbotService.getCourses().toPromise();
    this.currentOptions = courses!.data;
  }

  private async showInternships() {
    this.addBotMessage('We offer the following internship opportunities, Please select one:');
    const internships = await this.chatbotService.getInternships().toPromise();
    this.currentOptions = internships!.data;
  }

  private async showContactInfo() {
    const contact = await this.chatbotService.getContactInfo().toPromise();
    const data = contact!.data;
    this.addBotMessage(`
      <div class="contact-info">
        <strong>Contact Information</strong><br>
        <p><i class="bi bi-telephone"></i> Phone: ${data.primary_contact.phone}</p>
        <p><i class="bi bi-envelope"></i> Email: ${data.primary_contact.email}</p>
      </div>
    `);
  }

  showDetails(course: ChatOption) {
    this.addBotMessage(`
      <div class="course-link">
        For more information you can visit this link: <a href="${course.link}" target="_blank">${course.text + ' ' + this.activeOption} </a>
      </div>
    `);
    this.currentOptions = [];
  }

  restartChat() {
    this.messages = [];
    this.currentOptions = [];
    this.activeOption = '';
    this.addBotMessage(`Hello ${this.currentUser.name}! 👋<br>Hey there! Please select an option to get started.`);
    this.showMainOptions();
  }
}