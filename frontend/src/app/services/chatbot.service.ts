import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { User, ChatOption, MainOptions } from '../components/chatbot/chatbot.component';

export interface ContactInfo {
  data: any;
  timestamp: string;
  status: string;
  request_id: string;
}

@Injectable({
  providedIn: 'root'
})
export class ChatbotService {
  private apiUrl = 'http://localhost:8080/api';

  constructor(private http: HttpClient) {}

  saveUser(user: User): Observable<any> {
    return this.http.post(`${this.apiUrl}/save-user.php`, user);
  }

  getMainOptions(): Observable<MainOptions> {
    return this.http.get<MainOptions>(`${this.apiUrl}/get-main-options.php`);
  }

  getCourses(): Observable<MainOptions> {
    return this.http.get<MainOptions>(`${this.apiUrl}/get-courses.php`);
  }

  getInternships(): Observable<MainOptions> {
    return this.http.get<MainOptions>(`${this.apiUrl}/get-internships.php`);
  }

  getContactInfo(): Observable<ContactInfo> {
    return this.http.get<ContactInfo>(`${this.apiUrl}/get-contact.php`);
  }
}