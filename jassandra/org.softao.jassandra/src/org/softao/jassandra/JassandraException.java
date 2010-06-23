/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

/**
 * The Class JassandraException.
 */
public class JassandraException extends Exception {

	/** The Constant serialVersionUID. */
	private static final long serialVersionUID = 7902341969060978028L;

	/**
	 * Instantiates a new jassandra exception.
	 * 
	 * @param message
	 *            the message
	 */
	public JassandraException(String message) {
		super(message);
	}

	/**
	 * Instantiates a new jassandra exception.
	 * 
	 * @param message
	 *            the message
	 * @param throwable
	 *            the throwable
	 */
	public JassandraException(String message, Throwable throwable) {
		super(message, throwable);
	}

	/**
	 * Instantiates a new jassandra exception.
	 */
	public JassandraException() {
	}

}
