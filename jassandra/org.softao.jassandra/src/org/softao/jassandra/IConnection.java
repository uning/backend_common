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

import java.util.Map;

/**
 * A connection to a <code>Cassandra</code> cluster.
 */
public interface IConnection {

	/**
	 * Close the connection. After closed, the {@link #isClosed()} returns
	 * <code>true</code>.
	 * 
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	void close() throws JassandraException;

	/**
	 * Checks if is closed.
	 * 
	 * @return <code>true</code> if the connection has been closed;
	 * @throws JassandraException
	 *             the jassandra exception
	 */
	boolean isClosed() throws JassandraException;

	/**
	 * Gets the key space with specified <code>name</code>.
	 * 
	 * @param name
	 *            the name
	 * @return a key space with specified <code>name</code>. Throws
	 *         {@link JassandraException} if not existed.
	 * @throws JassandraException
	 *             throws if key space with specified <code>name</code> not
	 *             existed.
	 */
	IKeySpace getKeySpace(String name) throws JassandraException;

	/**
	 * Gets all key spaces in this cluster.
	 * 
	 * @return name to IKeySpace map
	 * @throws JassandraException
	 */
	Map<String, IKeySpace> getKeySpaces() throws JassandraException;
}
